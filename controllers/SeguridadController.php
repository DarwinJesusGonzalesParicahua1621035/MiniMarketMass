<?php
class SeguridadController extends AuthController
{
    protected bool $publico = false;
    protected array $roles = ['ADMINISTRADOR', 'CAJERO'];

    public function index(): void
    {
        $this->seguridad();
    }
}