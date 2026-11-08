<?php

namespace App\MessageHandler;

use App\Message\QualifyTicket;
use App\Repository\CategoryRepository;
use App\Repository\TicketRepository;
use App\Service\LlmClassifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsMessageHandler]
final class QualifyTicketHandler
{
    public function __construct(
        private readonly TicketRepository $tickets,
        private readonly CategoryRepository $categories,
        private readonly LlmClassifier $llm,
        private readonly WorkflowInterface $ticketStateMachine,
        private readonly EntityManagerInterface $em,
    ) {}

    public function __invoke(QualifyTicket $message): void
    {
        $ticket = $this->tickets->find($message->ticketId);
        if (!$ticket || !$this->ticketStateMachine->can($ticket, 'qualify')) {
            return;   // ticket supprimé ou déjà traité
        }

        $result = $this->llm->classify($ticket->getSubject(), $ticket->getMessage());

        if (null === $ticket->getCategory()) {
            $ticket->setCategory($this->categories->findOneBy(['name' => $result['category']]));
        }
        $ticket->setPriority($result['priority']);
        $ticket->setAiDraft($result['draft']);

        $this->ticketStateMachine->apply($ticket, 'qualify');
        $this->em->flush();
    }
}