<?php

namespace App\Core;

interface Request {
    public function header(string $name): ?string;
    public function setUser(array $user): void;
    public function getUser(): ?array;
}
