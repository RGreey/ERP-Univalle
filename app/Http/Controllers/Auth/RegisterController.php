<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Session;

class RegisterController extends Controller
{
    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }
    
    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        $user = $this->create($request->all());

        $this->guard()->login($user);

        return $this->registered($request, $user)
                        ?: redirect($this->redirectPath());
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'rol' => ['required', 'string', 'in:Estudiante,Profesor,Administrativo'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users',
                'regex:/^[a-zA-Z0-9._%+-]+@correounivalle\.edu\.co$/'
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.unique' => 'El correo electrónico ya ha sido registrado.',
            'email.regex' => 'El correo debe ser institucional (@correounivalle.edu.co).',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'rol' => 'Estudiante', // Siempre estudiante por defecto
            'rol_solicitado' => $data['rol'], // El rol solicitado
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Verificar si el correo coincide con el específico
        // Puedes cambiar 'administrativa.caicedoni@correounivalle.edu.co' por el correo deseado
        if ($user->email === 'administrativa.caicedoni@correounivalle.edu.co') {
            // Asignar el rol especial al usuario
            $user->rol = 'CooAdmin';

        } elseif ($user->email === 'auxiliar.administrativ@correounivalle.edu.co') {
            // Asignar el rol especial 'AuxAdmin' al usuario si su correo coincide con otro específico
            $user->rol = 'AuxAdmin';
        }

        $user->save();

        return $user;
    }
    
    /**
     * The user has been registered.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function registered(Request $request, $user)
    {
        
        
        Session::flash('registroExitoso', true);

        //$request->session()->invalidate();
        //$request->session()->regenerateToken();

        Alert::success('Registroexitoso', '¡Gracias por registrarte! Por favor, inicia sesión.');
        return redirect()->route('login');
    }
}
