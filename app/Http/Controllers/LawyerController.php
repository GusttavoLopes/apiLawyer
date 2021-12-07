<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Models\UserAppointment;
use App\Models\UserFavorite;
use App\Models\Lawyer;
use App\Models\LawyerPhotos;
use App\Models\LawyerServices;
use App\Models\LawyerTestimonial;
use App\Models\LawyerAvailability;

class LawyerController extends Controller
{
    private $loggedUser;

    public function __construct() {
        $this->middleware('auth:api');
        $this->loggedUser = auth()->user();
    }
    /*
    public function createRandom() {
        $array = ['error' => ''];

        for($q=0;$q<15;$q++) {
            $names = ['Dr. Juliano', 'Dra. Deolane', 'Dr. Willian', 'Dr Gustavo', 'Dra. Leticia', 'Dra. Vitoria', 'Dr. Marcio'];
            $lastnames = ['Silva', 'Lacerda', 'Diniz', 'Alvaro', 'Sousa', 'Gomes'];

            $servicos = ['Consulta Primária', 'Consulta Padrão', 'Consulta Específica'];
            $servicos2 = ['Trabalhista', 'Civil', 'TI', 'Contratual', 'Tributario'];

            $depos = [
                'Ótimo profissinal.',
                'O serviço prestado foi muito bom, consegui resolver todos meus assuntos.',
                'Trabalha com profissionalismo e respeito, mostrando resultados.',
                'Me ajudou muito em minhas causas.',
                'Advogado muito bom no que faz, me ajudou a resolver todos meus problemas.'
            ];

            $newLawyer = new Lawyer();
            $newLawyer->name = $names[rand(0, count($names)-1)].' '.$lastnames[rand(0, count($lastnames)-1)];
            $newLawyer->avatar = rand(1, 4).'.png';
            $newLawyer->stars = rand(2, 4).'.'.rand(0, 9);
            $newLawyer->latitude = '-23.5'.rand(0,9).'30907';
            $newLawyer->longitude = '-46.6'.rand(0,9).'82795';
            $newLawyer->save();

            $ns = rand(3, 6);

            for($w=0;$w<4;$w++) {
                $newLawyerPhoto = new LawyerPhotos();
                $newLawyerPhoto->id_lawyer = $newLawyer->id;
                $newLawyerPhoto->url = rand(1, 5).'.png';
                $newLawyerPhoto->save();
            }

            for($w=0;$w<$ns;$w++) {
                $newLawyerService = new LawyerServices();
                $newLawyerService->id_lawyer = $newLawyer->id;
                $newLawyerService->name = $servicos[rand(0, count($servicos)-1)].' - '.$servicos2[rand(0, count($servicos2)-1)];
                $newLawyerService->price = rand(100, 250).'.'.rand(0, 100);
                $newLawyerService->save();
            }

            for($w=0;$w<3;$w++) {
                $newLawyerTestimonial = new LawyerTestimonial();
                $newLawyerTestimonial->id_lawyer = $newLawyer->id;
                $newLawyerTestimonial->name = $names[rand(0, count($names)-1)].' '.$lastnames[rand(0, count($lastnames)-1)];
                $newLawyerTestimonial->rate = rand(2, 4).'.'.rand(0, 9);
                $newLawyerTestimonial->body = $depos[rand(0, count($depos)-1)];
                $newLawyerTestimonial->save();
            }

            for($e=0;$e<4;$e++) {
                $rAdd = rand(7, 10);
                $hours = [];
                for($r=0;$r<8;$r++) {
                    $time = $r + $rAdd;
                    if($time < 10) {
                        $time = '0'.$time;
                    }
                    $hours[] = $time.':00';
                }
                $newLawyerAvail = new LawyerAvailability();
                $newLawyerAvail->id_lawyer = $newLawyer->id;
                $newLawyerAvail->weekday = $e;
                $newLawyerAvail->hours = implode(',', $hours);
                $newLawyerAvail->save();
            }

        }

        return $array;
    }
    */
    

    private function searchGeo($address) {
        $key = env('MAPS_KEY', null);

        $address = urlencode($address);
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?address='.$address.'&key='.$key;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($ch);
        curl_close($ch);

        return json_decode($res, true);
    }

    public function list(Request $request) {
        $array = ['error' => ''];

        $lat = $request->input('lat');
        $lng = $request->input('lng');
        $city = $request->input('city');
        $offset = $request->input('offset');
        if(!$offset) {
            $offset = 0;
        }

        if(!empty($city)) {
            $res = $this->searchGeo($city);

            if(count($res['results']) > 0) {
                $lat = $res['results'][0]['geometry']['location']['lat'];
                $lng = $res['results'][0]['geometry']['location']['lng'];
            }
        } elseif(!empty($lat) && !empty($lng)) {
            $res = $this->searchGeo($lat.','.$lng);

            if(count($res['results']) > 0) {
                $city = $res['results'][0]['formatted_address'];
            }
        } else {
            $lat = '-23.5630907';
            $lng = '-46.6682795';
            $city = 'São Paulo';
        }

        $lawyers = Lawyer::select(Lawyer::raw('*, SQRT(
            POW(69.1 * (latitude - '.$lat.'), 2) +
            POW(69.1 * ('.$lng.' - longitude) * COS(latitude / 57.3), 2)) AS distance'))
            ->havingRaw('distance < ?', [10])
            ->orderBy('distance', 'ASC')
            ->offset($offset)
            ->limit(5)
            ->get();

        foreach($lawyers as $bkey => $bvalue) {
            $lawyers[$bkey]['avatar'] = url('media/avatars/'.$lawyers[$bkey]['avatar']);
        }

        $array['data'] = $lawyers;
        $array['loc'] = 'São Paulo';

        return $array;
    }

    public function one($id) {
        $array = ['error' => ''];

        $lawyer = Lawyer::find($id);

        if($lawyer) {
            $lawyer['avatar'] = url('media/avatars/'.$lawyer['avatar']);
            $lawyer['favorited'] = false;
            $lawyer['photos'] = [];
            $lawyer['services'] = [];
            $lawyer['testimonials'] = [];
            $lawyer['available'] = [];

            // Verificando favorito
            $cFavorite = UserFavorite::where('id_user', $this->loggedUser->id)
                ->where('id_lawyer', $lawyer->id)
                ->count();
            if($cFavorite > 0) {
                $lawyer['favorited'] = true;
            }            

            // Pegando as fotos do Advogado
            $lawyer['photos'] = LawyerPhotos::select(['id', 'url'])
                ->where('id_lawyer', $lawyer->id)
                ->get();
            foreach($lawyer['photos'] as $bpkey => $bpvalue) {
                $lawyer['photos'][$bpkey]['url'] = url('media/uploads/'.$lawyer['photos'][$bpkey]['url']);
            }

            // Pegando os serviços do Advogado
            $lawyer['services'] = LawyerServices::select(['id', 'name', 'price'])
                ->where('id_lawyer', $lawyer->id)
                ->get();

            // Pegando os depoimentos do Advogado
            $lawyer['testimonials'] = LawyerTestimonial::select(['id', 'name', 'rate', 'body'])
                ->where('id_lawyer', $lawyer->id)
                ->get();

            // Pegando disponibilidade do Advogado
            $availability = [];

            // - Pegando a disponibilidade crua
            $avails = LawyerAvailability::where('id_lawyer', $lawyer->id)->get();
            $availWeekdays = [];
            foreach($avails as $item) {
                $availWeekdays[$item['weekday']] = explode(',', $item['hours']);
            }

            // - Pegar os agendamentos dos próximos 20 dias
            $appointments = [];
            $appQuery = UserAppointment::where('id_lawyer', $lawyer->id)
                ->whereBetween('ap_datetime', [
                    date('Y-m-d').' 00:00:00',
                    date('Y-m-d', strtotime('+20 days')).' 23:59:59'
                ])
                ->get();
            foreach($appQuery as $appItem) {
                $appointments[] = $appItem['ap_datetime'];
            }

            // - Gerar disponibilidade real
            for($q=0;$q<20;$q++) {
                $timeItem = strtotime('+'.$q.' days');
                $weekday = date('w', $timeItem);

                if(in_array($weekday, array_keys($availWeekdays))) {
                    $hours = [];

                    $dayItem = date('Y-m-d', $timeItem);

                    foreach($availWeekdays[$weekday] as $hourItem) {
                        $dayFormated = $dayItem.' '.$hourItem.':00';
                        if(!in_array($dayFormated, $appointments)) {
                            $hours[] = $hourItem;
                        }
                    }

                    if(count($hours) > 0) {
                        $availability[] = [
                            'date' => $dayItem,
                            'hours' => $hours
                        ];
                    }

                }
            }

            $lawyer['available'] = $availability;

            $array['data'] = $lawyer;
        } else {
            $array['error'] = 'Advogado não existe';
            return $array;
        }

        return $array;
    }

    public function setAppointment($id, Request $request) {
        // service, year, month, day, hour
        $array = ['error'=>''];

        $service = $request->input('service');
        $year = intval($request->input('year'));
        $month = intval($request->input('month'));
        $day = intval($request->input('day'));
        $hour = intval($request->input('hour'));

        $month = ($month < 10) ? '0'.$month : $month;
        $day = ($day < 10) ? '0'.$day : $day;
        $hour = ($hour < 10) ? '0'.$hour : $hour;

        // 1. verificar se o serviço do advogado existe
        $lawyerservice = LawyerServices::select()
            ->where('id', $service)
            ->where('id_lawyer', $id)
        ->first();

        if($lawyerservice) {
            // 2. verificar se a data é real
            $apDate = $year.'-'.$month.'-'.$day.' '.$hour.':00:00';
            if(strtotime($apDate) > 0) {
                // 3. verificar se o advogado já possui agendamento neste dia/hora
                $apps = UserAppointment::select()
                    ->where('id_lawyer', $id)
                    ->where('ap_datetime', $apDate)
                ->count();
                if($apps === 0) {
                    // 4.1 verificar se o advogado atende nesta data
                    $weekday = date('w', strtotime($apDate));
                    $avail = LawyerAvailability::select()
                        ->where('id_lawyer', $id)
                        ->where('weekday', $weekday)
                    ->first();
                    if($avail) {
                        // 4.2 verificar se o advogado atende nesta hora
                        $hours = explode(',', $avail['hours']);
                        if(in_array($hour.':00', $hours)) {
                            // 5. fazer o agendamento
                            $newApp = new UserAppointment();
                            $newApp->id_user = $this->loggedUser->id;
                            $newApp->id_lawyer = $id;
                            $newApp->id_service = $service;
                            $newApp->ap_datetime = $apDate;
                            $newApp->save();
                        } else {
                            $array['error'] = 'Advogado não atende nesta hora';
                        }
                    } else {
                        $array['error'] = 'Advogado não atende neste dia';
                    }                    
                } else {
                    $array['error'] = 'Advogado já possui agendamento neste dia/hora';
                }
            } else {
                $array['error'] = 'Data inválida';
            }
        } else {
            $array['error'] = 'Serviço inexistente!';
        }
        return $array;
    }

    public function search(Request $request) {
        $array = ['error'=>'', 'list'=>[]];

        $q = $request->input('q');

        if($q) {

            $lawyers = Lawyer::select()
                ->where('name', 'LIKE', '%'.$q.'%')
            ->get();

            foreach($lawyers as $bkey => $lawyer) {
                $lawyers[$bkey]['avatar'] = url('media/avatars/'.$lawyers[$bkey]['avatar']);
            }

            $array['list'] = $lawyers;
        } else {
            $array['error'] = 'Digite algo para buscar';
        }

        return $array;
    }
}
