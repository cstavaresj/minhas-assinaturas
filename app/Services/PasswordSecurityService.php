<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class PasswordSecurityService
{
    /**
     * Aplica sal e pimenta na senha seguindo os requisitos de seguranca.
     * Sal: adicionado antes de encriptar.
     * Pimenta: adicionada apos encriptar.
     */
    public static function hashPassword(string $plainPassword): string
    {
        $salt = config('app.password_salt');
        $pepper = config('app.password_pepper');

        $saltedPassword = $plainPassword . $salt;
        $hashed = Hash::make($saltedPassword);

        return $hashed . $pepper;
    }

    /**
     * Verifica a senha separando a pimenta e validando o sal.
     */
    public static function checkPassword(string $plainPassword, string $storedHashWithPepper): bool
    {
        $salt = config('app.password_salt');
        $pepper = config('app.password_pepper');

        try {
            if (! Str::endsWith($storedHashWithPepper, $pepper)) {
                return Hash::check($plainPassword, $storedHashWithPepper);
            }

            $originalHash = Str::replaceLast($pepper, '', $storedHashWithPepper);
            $saltedPassword = $plainPassword . $salt;

            return Hash::check($saltedPassword, $originalHash);
        } catch (RuntimeException) {
            return false;
        }
    }
}
