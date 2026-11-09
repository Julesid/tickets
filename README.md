# Tickets — centralisation des incidents avec qualification IA

Mini-outil interne que j'ai construit pour préparer mon passage dev :
centraliser les tickets (support, bugs, infra) et les pré-qualifier avec un LLM.

## Stack
Symfony 6.4 · Doctrine · Workflow · Messenger · HttpClient · Twig/Bootstrap · PHPUnit · API Claude

## Comment ça marche
1. Création du ticket → classification immédiate par mots-clés (gratuit, instantané)
2. Dispatch d'un message `QualifyTicket` → worker Messenger asynchrone
3. Le worker appelle Claude : catégorie (si non trouvée), priorité, brouillon de réponse
4. Sortie JSON validée par liste blanche, puis transition `new → qualified`

## Choix techniques
- L'IA ne bloque jamais le support : retry ×3 puis transport `failed`
- Handler idempotent (`can('qualify')`) : un message traité deux fois ne fait rien
- Le LLM propose, l'humain dispose : aucune action automatique, brouillon échappé
- Statut modifiable uniquement via les transitions du Workflow

## Limites connues / suite
- Mots-clés : pas de gestion des pluriels ni des expressions multi-mots
- Pistes : sorties structurées (schéma JSON), RAG sur les anciens tickets, authentification

## Lancer en local
composer install · cp .env .env.local (DATABASE_URL, ANTHROPIC_API_KEY)
php bin/console doctrine:migrations:migrate
php bin/console messenger:consume async -vv