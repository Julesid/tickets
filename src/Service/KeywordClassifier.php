<?php

namespace App\Service;

final class KeywordClassifier
{
    private const KEYWORDS = [
        'DNS'         => ['dns', 'zone', 'propagation', 'mx', 'cname', 'ns'],
        'Mail'        => ['mail', 'email', 'smtp', 'imap', 'roundcube', 'spam', 'mails'],
        'WordPress'   => ['wordpress', 'wp-admin', 'plugin', 'extension', 'thème'],
        'Facturation' => ['facture', 'paiement', 'renouvellement', 'remboursement'],
    ];

    /** @return array{category: ?string, score: float} */
    public function classify(string $text): array
    {
        $text  = mb_strtolower($text);
        $words = preg_split('/[^\p{L}\p{N}-]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $hits  = [];
        $total = 0;

        foreach (self::KEYWORDS as $category => $keywords) {
            $hits[$category] = 0;
            foreach ($keywords as $keyword) {
                if (in_array($keyword, $words, true)) {
                    $hits[$category]++;
                    $total++;
                }
            }
        }

        if ($total === 0) {
            return ['category' => null, 'score' => 0.0];
        }

        $bestCategory = null;
        $bestCount = 0;
        foreach ($hits as $category => $count) {
            if ($count > $bestCount) {
                $bestCount = $count;
                $bestCategory = $category;
            }
        }

        return ['category' => $bestCategory, 'score' => (float) ($bestCount / $total)];
    }
}