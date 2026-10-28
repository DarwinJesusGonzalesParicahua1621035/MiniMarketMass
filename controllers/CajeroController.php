<?php
class CajeroController extends Controller
{
    protected array $roles = ['CAJERO', 'ADMINISTRADOR'];

    public function index(): void
    {
        $this->render('cajero/index', ['titulo' => 'Caja'], false);
    }
}