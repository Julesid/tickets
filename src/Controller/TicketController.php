<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Form\TicketType;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\WorkflowInterface;
use App\Service\KeywordClassifier;
use App\Repository\CategoryRepository;

#[Route('/ticket')]
final class TicketController extends AbstractController
{
    #[Route(name: 'app_ticket_index', methods: ['GET'])]
    public function index(TicketRepository $ticketRepository): Response
    {
        return $this->render('ticket/index.html.twig', [
            'tickets' => $ticketRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_ticket_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, KeywordClassifier $classifier, CategoryRepository $categoryRepository): Response
    {
        $ticket = new Ticket();
        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $result = $classifier->classify($ticket->getSubject().' '.$ticket->getMessage());
                if (null === $ticket->getCategory() && $result['score'] >= 0.6) {
                    $ticket->setCategory($categoryRepository->findOneBy(['name' => $result['category']]));
                }
            $entityManager->persist($ticket);
            $entityManager->flush();

            return $this->redirectToRoute('app_ticket_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ticket/new.html.twig', [
            'ticket' => $ticket,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_ticket_show', methods: ['GET'])]
    public function show(Ticket $ticket): Response
    {
        return $this->render('ticket/show.html.twig', [
            'ticket' => $ticket,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_ticket_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Ticket $ticket, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_ticket_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ticket/edit.html.twig', [
            'ticket' => $ticket,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_ticket_delete', methods: ['POST'])]
    public function delete(Request $request, Ticket $ticket, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $ticket->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($ticket);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_ticket_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/transition/{transition}', name: 'app_ticket_transition', methods: ['POST'])]
    public function transition(
        Ticket $ticket,
        string $transition,
        Request $request,
        WorkflowInterface $ticketStateMachine,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('transition' . $ticket->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        if (!$ticketStateMachine->can($ticket, $transition)) {
            $this->addFlash('danger', sprintf('Transition « %s » impossible depuis « %s ».', $transition, $ticket->getStatus()));
        } else {
            $ticketStateMachine->apply($ticket, $transition);
            $em->flush();
            $this->addFlash('success', 'Statut : ' . $ticket->getStatus());
        }

        return $this->redirectToRoute('app_ticket_show', ['id' => $ticket->getId()]);
    }
}
