<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

#[Layout('layouts.auth')]
class Login extends Component
{
    public $username = '';
    public $password = '';

    public function prosesLogin()
    {
        $this->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        // i already added into web.php but it still didn't work, so i have to apply it into here. about the throttle limiter

        // 1. create an unique key based on the user's IP address
        $throttleKey = 'login-attempt:' . request()->ip();

        // 2. check if they have hit the limit (5 attempts) BEFORE checking the database
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            // return an error to the UI
            $this->addError('login', "Terlalu banyak percobaan. Silahkan coba lagi dalam {$seconds} detik.");
            return;
        }

        // 3. attempt authentication
        if (Auth::attempt(['username' => $this->username, 'password' => $this->password])) {
            session()->regenerate();

            // 4. important! clear the counter on a succesfull login
            RateLimiter::clear($throttleKey);

            return redirect()->route('dashboard');
        }

        // 5. if login fails, add +1 to their attempt counter (decaying after 60 seconds)
        RateLimiter::hit($throttleKey, 60);
        
        $this->addError('login', 'Username atau Password salah!');
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}