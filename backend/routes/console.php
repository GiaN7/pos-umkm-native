<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('pos:create-owner {email?} {--name=}', function (?string $email) {
    $name = $this->option('name') ?: $this->ask('Nama owner', 'Owner');
    $email = strtolower(trim($email ?: $this->ask('Email owner', 'owner@pos.com')));

    if (! is_string($name) || trim($name) === '' || strlen($name) > 255) {
        $this->error('Nama owner wajib diisi dan maksimal 255 karakter.');

        return self::FAILURE;
    }

    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('Format email tidak valid.');

        return self::FAILURE;
    }

    $user = User::where('email', $email)->first();
    if ($user && ! $this->confirm(
        "Akun {$email} sudah ada dengan role {$user->role}. Jadikan owner dan ganti password?",
        false,
    )) {
        $this->info('Tidak ada perubahan.');

        return self::FAILURE;
    }

    $password = $this->secret('Password baru (minimal 8 karakter)');
    $confirmation = $this->secret('Ulangi password baru');

    if (! is_string($password) || strlen($password) < 8) {
        $this->error('Password minimal 8 karakter.');

        return self::FAILURE;
    }

    if (! is_string($confirmation) || ! hash_equals($password, $confirmation)) {
        $this->error('Konfirmasi password tidak cocok.');

        return self::FAILURE;
    }

    $user ??= new User;
    $user->name = trim($name);
    $user->email = $email;
    $user->password = Hash::make($password);
    $user->role = 'owner';
    $user->status = true;
    $user->save();
    $user->tokens()->delete();

    $this->info("Akun owner {$email} berhasil disimpan. Gunakan password baru untuk login.");
})->purpose('Create or reset an owner account interactively');

Artisan::command('pos:create-kasir {email?} {--name=}', function (?string $email) {
    $name = $this->option('name') ?: $this->ask('Nama kasir', 'Kasir');
    $email = strtolower(trim($email ?: $this->ask('Email kasir', 'kasir@pos.com')));

    if (! is_string($name) || trim($name) === '' || strlen($name) > 255) {
        $this->error('Nama kasir wajib diisi dan maksimal 255 karakter.');

        return self::FAILURE;
    }

    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('Format email tidak valid.');

        return self::FAILURE;
    }

    $user = User::where('email', $email)->first();
    if ($user && ! $this->confirm(
        "Akun {$email} sudah ada dengan role {$user->role}. Jadikan kasir dan ganti password?",
        false,
    )) {
        $this->info('Tidak ada perubahan.');

        return self::FAILURE;
    }

    $password = $this->secret('Password baru (minimal 8 karakter)');
    $confirmation = $this->secret('Ulangi password baru');

    if (! is_string($password) || strlen($password) < 8) {
        $this->error('Password minimal 8 karakter.');

        return self::FAILURE;
    }

    if (! is_string($confirmation) || ! hash_equals($password, $confirmation)) {
        $this->error('Konfirmasi password tidak cocok.');

        return self::FAILURE;
    }

    $user ??= new User;
    $user->name = trim($name);
    $user->email = $email;
    $user->password = Hash::make($password);
    $user->role = 'kasir';
    $user->status = true;
    $user->save();
    $user->tokens()->delete();

    $this->info("Akun kasir {$email} berhasil disimpan. Gunakan password baru untuk login.");
})->purpose('Create or reset a cashier account interactively');
