<?php

namespace App\Tests\Service;

use App\Service\KeywordClassifier;
use PHPUnit\Framework\TestCase;

class KeywordClassifierTest extends TestCase
{
    public function testDetecteDns(): void
    {
        $result = (new KeywordClassifier())->classify('Mon DNS ne se propage pas, la zone est vide');

        $this->assertSame('DNS', $result['category']);
        $this->assertSame(1.0, $result['score']);
    }

    public function testPiegeNsDansDans(): void
    {
        $result = (new KeywordClassifier())->classify("Je n'arrive pas à me connecter dans mon espace client");

        $this->assertNull($result['category']);
    }
     public function testDeuxCategories(): void
    {
        $result = (new KeywordClassifier())->classify("Ma facture pour le renouvellement du domaine, et mes mails ne partent plus");

        $this->assertSame('Facturation', $result['category']);
        $this->assertSame(2/3, $result['score']);
    }
}