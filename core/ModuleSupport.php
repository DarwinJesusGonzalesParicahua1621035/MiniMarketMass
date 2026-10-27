<?php
class ModuleSupport
{
    public static function escape(string|int|float|null $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    public static function flash(string $type, string $message): void
    {
        $_SESSION['module_flash'] = [$type, $message];
    }

    public static function consumeFlash(): ?array
    {
        $flash = $_SESSION['module_flash'] ?? null;
        unset($_SESSION['module_flash']);
        return $flash;
    }

    public static function redirect(string $path): never
    {
        header('Location: ' . $path);
        exit;
    }

    public static function render(string $view, string $title, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $pageTitle = $title;
        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/' . $view . '.php';
        require __DIR__ . '/../views/layout/footer.php';
    }
}
