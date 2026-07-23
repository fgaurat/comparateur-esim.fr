<?php

/**
 * Contrat commun des backends de contenu.
 * Implémenté par Content (Markdown) et ContentSQLite.
 */
interface ContentInterface
{
    // ── Posts ─────────────────────────────────────────────────────────────────

    public function getPosts(array $args = []): array;

    public function getPostsCount(array $args = []): int;

    public function getPost(string $idOrSlug): ?array;

    public function createPost(array $data): ?array;

    public function updatePost(string $idOrSlug, array $data): ?array;

    public function deletePost(string $idOrSlug, bool $force = false): ?array;

    // ── Pages ─────────────────────────────────────────────────────────────────

    public function getPages(array $args = []): array;

    public function getPage(string $idOrSlug): ?array;

    public function createPage(array $data): ?array;

    public function updatePage(string $idOrSlug, array $data): ?array;

    public function deletePage(string $idOrSlug, bool $force = false): ?array;

    // ── Médias ────────────────────────────────────────────────────────────────

    public function getMedia(): array;

    public function getMediaItem(string $idOrSlug): ?array;

    // ── Taxonomie ─────────────────────────────────────────────────────────────

    public function getCategories(): array;

    public function getTags(): array;

    // ── Hiérarchie géographique ───────────────────────────────────────────────

    public function getPageByPath(string $path): ?array;

    public function getChildPages(int $parentId, array $args = []): array;

    // ── Cache ─────────────────────────────────────────────────────────────────

    public function clearCache(): void;
}
