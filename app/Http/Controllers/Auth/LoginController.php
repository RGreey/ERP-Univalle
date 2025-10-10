<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Validate the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
        ], [
            $this->username() . '.required' => 'El correo es requerido.',
            'password.required' => 'La contraseña es requerida.',
        ]);
    }

    /**
     * Attempt to log the user into the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function attemptLogin(Request $request)
    {
        return Auth::attempt($this->credentials($request));
    }

    /**
     * Get the failed login response instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        return redirect()->back()
            ->withInput($request->only($this->username(), 'remember'))
            ->withErrors([
                'password' => 'Contraseña incorrecta, por favor intenta de nuevo.',
            ]);
    }

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {

        $role = $user->rol;


        session(['role' => $role]);

        switch ($role) {
            case 'Estudiante':
                return redirect()->route('estudiante.dashboard');
                break;
            case 'Profesor':
                return redirect()->route('profesor.dashboard');
                break;
            case 'CooAdmin':
                return redirect()->route('administrativo.dashboard');
                break;
            case 'Administrativo':
                return redirect()->route('administrativo.dashboard');
                break;
            case 'AuxAdmin':
                return redirect()->route('administrativo.dashboard');
                break;
            case 'AdminBienestar':
                return redirect()->route('subsidio.admin.dashboard');
            case 'Restaurante':
                return redirect()->route('restaurantes.dashboard');
            default:
                return redirect('/'); 
                break;
        }
    }
}
