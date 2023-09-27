<?php

namespace App\Http\Controllers;

use App\Models\Armada;
use App\Models\Loket;
use App\Models\Nahkoda;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;

class AuthManualController extends Controller
{
    //
    public function storeArmada(Request $request) {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|min:5',
            'type' => 'required'
        ]);

        $user = new User([
            'email' => $request->input('email'),
            'password' => bcrypt($request->input('password')),
            'type' => $request->input('type')
        ]);

        $credential = [
            'email' => $request->input('email'),
            'password' => $request->input('password')
        ];

        if ($user->save()) {
            $response = [
                'message' => 'User Created',
                'user' => $user,
            ];

            return response()->json($response, 200);
        }

        return response()->json(['User not Created'], 404);
    }

    public function storeLoket(Request $request) {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|min:5',
            'type' => 'required',
            'nama_loket' => 'required|string',
            'lokasi_loket' => 'required|string',
            'id_armada' => 'required'
        ]);

        $user = new User([
            'email' => $request->input('email'),
            'password' => bcrypt($request->input('password')),
            'type' => $request->input('type')
        ]);

        $credential = [
            'email' => $request->input('email'),
            'password' => $request->input('password')
        ];

        if ($user->save()) {

            $loket = new Loket([
                'id_loket' => $user['id'],
                'nama_loket' => $request->input('nama_loket'),
                'lokasi_loket' => $request->input('lokasi_loket'),
                'id_armada' => $request->input('id_armada')
            ]);

            if ($loket->save()) {
                $response = [
                    'message' => 'User Created',
                    'user' => $user,
                    'loket' => $loket,
                ];

                return response()->json($response, 201);
            }

            return response()->json(['User not Created'], 404);
        }

        return response()->json(['User not Created'], 404);
    }

    public function storeNahkoda(Request $request) {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|min:5',
            'type' => 'required',
            'nama_nahkoda' => 'required|string',
            'no_hp' => 'required|string',
            'id_armada' => 'required'
        ]);

        $user = new User([
            'email' => $request->input('email'),
            'password' => bcrypt($request->input('password')),
            'type' => $request->input('type')
        ]);

        $credential = [
            'email' => $request->input('email'),
            'password' => $request->input('password')
        ];

        if ($user->save()) {

            $nahkoda = new Nahkoda([
                'id_nahkoda' => $user['id'],
                'nama_nahkoda' => $request->input('nama_nahkoda'),
                'no_hp' => $request->input('no_hp'),
                'id_armada' => $request->input('id_armada'),
                'id_kecakapan' => $request->input('id_kecakapan')
            ]);

            if ($nahkoda->save()) {
                $response = [
                    'message' => 'User Created',
                    'user' => $user,
                    'nahkoda' => $nahkoda,
                ];

                return response()->json($response, 201);
            }

            return response()->json(['User not Created'], 404);
        }

        return response()->json(['User not Created'], 404);
    }

    public function login(Request $request) {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|min:5'
        ]);

        $email = $request->input('email');
        $password = $request->input('password');

        if ($user = User::query()->where('email', $email)->first()) {
            $credential = [
                'email' => $email,
                'password' => $password
            ];

            $token = null;
            
                if (!$token = JWTAuth::attempt($credential)) {
                    return response()->json([
                        'message' => 'Email or Password are incorrect'
                    ], 404);
                }
            

            if ($user['type'] == 'armada' || $user['type'] == 'pelabuhan') {
                $response = [
                    'message' => 'armada signin',
                    'user' => $user,
                    'armada' => Armada::query()->where('id_user', $user['id'])->firstOrFail(),
                    'token' => $token
                ];
            }
            elseif ($user['type'] == 'nahkoda') {
                $nahkoda = Nahkoda::query()->where('id_nahkoda', $user['id'])->firstOrFail();
                $armada = Armada::query()->where('id_armada', $nahkoda['id_armada'])->firstOrFail();
                $type = User::query()->where('id', $armada['id_user'])->firstOrFail();
                $response = [
                    'message' => 'nahkoda signin',
                    'user' => $user,
                    'nahkoda' => $nahkoda,
                    'type_armada' => $type['type'],
                    'armada' => $armada['nama_armada'],
                    'token' => $token
                ];
            }
            elseif ($user['type'] == 'loket') {
                $response = [
                    'message' => 'loket signin',
                    'user' => $user,
                    'loket' => Loket::query()->where('id_loket', $user['id'])->firstOrFail(),
                    'token' => $token
                ];
            }
            elseif ($user['type'] == 'admin') {
                $response = [
                    'message' => 'admin signin',
                    'user' => $user,
                    'token' => $token
                ];
            }
            elseif ($user['type'] == 'syahbandar') {
                $response = [
                    'message' => 'syahbandar signin',
                    'user' => $user,
                    'token' => $token
                ];
            }
            elseif ($user['type'] == 'wisata') {
                $response = [
                    'message' => 'wisata signin',
                    'user' => $user,
                    'token' => $token
                ];
            }
             elseif ($user['type'] == 'pelapor') {
                $response = [
                    'message' => 'pelapor signin',
                    'user' => $user,
                    'token' => $token
                ];
            }
            else {
                $response = [
                    'message' => 'Email tidak terdaftar'
                ];
            }

            return response()->json($response, 200);
        }
        else {
            return response()->json(['message' => 'Email not found'], 404);
        }
    }
    
    public function index() {
        $response = DB::select("SELECT * FROM `users` where type = 'admin' or type = 'syahbandar' or type = 'wisata' or type = 'pelapor'");

        return response()->json($response, 200);
    }
    
    public function index_syahbandar() {
        $response = DB::select("SELECT * FROM `users` where type = 'syahbandar'");

        return response()->json($response, 200);
    }

    public function changePassword(Request $request) {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|min:5',
            'password_confirmation' => 'required|min:5'
        ]);
        if ($request->input('password') === $request->input('password_confirmation')) {
            $user = User::where('email', $request->input('email'))->first();
            $user->password = bcrypt($request->input('password'));
            $user->save();
            return response()->json(['message' => 'Password berhasil diubah.', 'user' => $user], 200);
        }
        return response()->json(['message' => 'Password gagal diubah.'], 200);
    }
}
