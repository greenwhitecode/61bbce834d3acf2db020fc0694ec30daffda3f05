<?php

namespace App\Http\Controllers\Admin;

use App\Transaction;
use App\User;
use App\User_meta;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminAddController extends Controller
{
    //
    public function __construct(AdminController $details)
    {
        $this->details = $details;
    }

    protected function validateUser(array $data)
    {
        return Validator::make($data, [
            'first_name'            => 'required|string|max:255',
            'last_name'             => 'string|max:255',
            'other_name'            => 'max:255',
            'account_number'        => 'required|string|unique:user_metas|max:10',
            'wallet_address'        => 'required|string|unique:user_metas|max:255',
            'private_key'           => 'required|string|unique:user_metas|max:255',
            'dob'                   => 'required|date|max:255',
            'marital_status'        => 'required|string|max:255',
            'gender'                => 'required|string|max:255',
            'phone_no'              => 'required|string|unique:user_metas|max:255',
            'nationality'           => 'required|string|max:255',
            'state'                 => 'required|string|max:255',
            'lga'                   => 'required|string|max:255',
            'residential_address'   => 'required|string|max:255',
            'contact_address'       => 'required|string|max:255',
            'id_card_type'          => 'required|string|max:255',
            'id_card_no'            => 'required|unique:user_metas|max:255',
            'bvn'                   => 'required|numeric|unique:user_metas',
            'bank_name'             => 'string|max:255',
            'bank_acc_name'         => 'string|max:255',
            'bank_acc_no'           => 'numeric|unique:user_metas',
            'occupation'            => 'required|string|max:255',
            'next_of_kin'           => 'required|string|max:255',
            'nok_relationship'      => 'required|string|max:255',
            'nok_contact_address'   => 'required|string|max:255',
            'nok_dob'               => 'required|date|max:255',
            'nok_gender'            => 'required|string|max:255',
            'nok_phone_no'          => 'required|string|max:255',
            'nok_email'             => 'required|string|max:255',
            'spouse_name'           => 'required|string|max:255',
            'mother_maiden_name'    => 'required|string|max:255',
            'office_phone_no'       => 'required|string|max:255',
            'landmark'              => 'required|string|max:255',
            'form_location'         => 'required|image',
            'signature_location'    => 'required|image',
            'utility_bill_location' => 'required|image',
            'idcard_location'       => 'required|image',
            'passport_location'     => 'required|image',
        ]);
    }

    public function validateAdmin(array $data)
    {
        return Validator::make($data, [
            'name'  => 'required|string|unique:users|max:255',
            'email' => 'required|string|email|max:255|unique:users',
        ]);
    }

    public function addPNM(Request $request)
    {
        $value = $this->details->getCurrentValue();
        $pnm = $request->input('amount');
        $pin = $request->input('pin');

        $data['action'] = 'new PNM';
        $checkPin = Hash::check($pin, Auth::user()->pin);
        if ($checkPin) {
            $ngn = $pnm * (int)$value;
            $description = "Admin added $pnm PNM worth $ngn NGN";
            $type = "admin-holding";
            $transaction = new Transaction();
            $transactionID = md5(Auth::user()->wallet_id . $pnm . $ngn
                . date('YFlHisuA'));
            $transaction->transaction_id = $transactionID;
            $transaction->from = Auth::user()->name;
            $transaction->to = Auth::user()->wallet_id;
            $transaction->amount = $pnm;
            $transaction->value = $value;
            $transaction->description = $description;
            $transaction->type = $type;
            $transaction->status = 'successful';
            $transaction->remark = 'credit';
            $transaction->save();
            if ($transaction->save()) {
                $data['alert'] = 'success';
                $data['message'] = "Your transaction was successful";
            }
        } elseif (!$checkPin) {
            $data['alert'] = 'danger';
            $data['message'] = "Sorry, the pin you entered was incorrect";
        }

        return view('admin.newPNM', $data);
    }

    public function addAdmin(Request $request)
    {
        $details = $request->all();
        $this->validateAdmin($details)->validate();
        $isAcc = true;
        $acc_no = '';
        while ($isAcc) {
            $acc_no = rand(1, 100000);
            $isAcc = User::where('account_number', $acc_no)->first();
        }
        $admin = User::create([
            'first_name'     => $details['first_name'],
            'last_name'      => $details['last_name'],
            'wallet_id'      => md5($details['email'] . date('YmdHis')),
            'name'           => $details['name'],
            'email'          => $details['email'],
            'password'       => bcrypt(md5($details['email'])),
            'pin'            => bcrypt('1234'),
            'account_number' => $acc_no,
            'wallet_address' => md5($details['email']),
            'private_key'    => md5($details['email']),
            'type'           => 'admin',
            'status'         => 'active',
            'access_level'   => $details['level'],
        ]);

        if ($admin) {
            $data['alert'] = 'success';
            $data['message'] = "New Admin was created successfully";

        } else {
            $data['alert'] = 'danger';
            $data['message'] = "There was an error in creating the admin";

        }
        $data['action'] = 'new admin';
        return view('admin.newAdmin', $data);
    }

    public function addUser(Request $request)
    {
        $details = $request->all();
        $this->validateUser($details)->validate();

        if ($request->hasFile('form_location')
            && $request->hasFile('signature_location')
            && $request->hasFile('utility_bill_location')
            && $request->hasFile('idcard_location')
            && $request->hasFile('passport_location')
        ) {
            $formImage = $request->file('form_location');
            $signatureImage = $request->file('signature_location');
            $utilityImage = $request->file('utility_bill_location');
            $idcardImage = $request->file('idcard_location');
            $passportImage = $request->file('passport_location');
            if ($formImage->isValid() && $signatureImage->isValid()
                && $utilityImage->isValid()
                && $idcardImage->isValid()
                && $passportImage->isValid()
            ) {
                /*$details['form_location'] = str_replace('public',
                    'storage', $signatureImage->store('public/images/forms'));*/
                $details['form_location']
                    = $signatureImage->store('tlssavings/public/images/forms');
                $details['signature_location']
                    = $signatureImage->store('tlssavings/public/images/signatures');
                $details['utility_bill_location']
                    = $utilityImage->store('tlssavings/public/images/utility_bills');
                $details['idcard_location']
                    = $idcardImage->store('tlssavings/public/images/idcards');
                $details['passport_location']
                    = $passportImage->store('tlssavings/public/images/passport');
            }
        }

        $user = $this->createUsers($details);
        if ($user) {
            $data['alert'] = 'success';
            $data['message'] = "User was created successfully";

        } else {
            $data['alert'] = 'danger';
            $data['message'] = "There was an error in creating the user";

        }
        $data['action'] = 'new user';
        return view('admin.newUser', $data);
    }

    protected function createUsers(array $data)
    {
        return User_meta::create([
            'first_name'            => $data['first_name'],
            'last_name'             => $data['last_name'],
            'other_name'            => $data['other_name'],
            'account_number'        => $data['account_number'],
            'wallet_address'        => $data['wallet_address'],
            'private_key'           => $data['private_key'],
            'dob'                   => date_create($data['dob']),
            'marital_status'        => $data['marital_status'],
            'gender'                => $data['gender'],
            'phone_no'              => $data['phone_no'],
            'nationality'           => $data['nationality'],
            'state'                 => $data['state'],
            'lga'                   => $data['lga'],
            'residential_address'   => $data['residential_address'],
            'contact_address'       => $data['contact_address'],
            'id_card_type'          => $data['id_card_type'],
            'id_card_no'            => $data['id_card_no'],
            'bvn'                   => $data['bvn'],
            'bank_name'             => $data['bank_name'],
            'bank_acc_name'         => $data['bank_acc_name'],
            'bank_acc_no'           => $data['bank_acc_no'],
            'occupation'            => $data['occupation'],
            'next_of_kin'           => $data['next_of_kin'],
            'nok_relationship'      => $data['nok_relationship'],
            'nok_contact_address'   => $data['nok_contact_address'],
            'nok_dob'               => date_create($data['nok_dob']),
            'nok_gender'            => $data['nok_gender'],
            'nok_phone_no'          => $data['nok_phone_no'],
            'nok_email'             => $data['nok_email'],
            'spouse_name'           => $data['spouse_name'],
            'mother_maiden_name'    => $data['mother_maiden_name'],
            'office_phone_no'       => $data['office_phone_no'],
            'landmark'              => $data['landmark'],
            'form_location'         => $data['form_location'],
            'signature_location'    => $data['signature_location'],
            'utility_bill_location' => $data['utility_bill_location'],
            'idcard_location'       => $data['idcard_location'],
            'passport_location'     => $data['passport_location'],
            'status'                => 'unregistered'
        ]);
    }

    public function uploadFiles(Request $request)
    {
        $details = array();

        return $details;
    }

    public function viewAddUser($data = array())
    {
        $data['action'] = 'new user';
        return view('admin.newUser', $data);
    }

    public function viewAddAdmin()
    {
        $data['action'] = 'new admin';
        return view('admin.newAdmin', $data);
    }

    public function viewAddPnm()
    {
        $data['action'] = 'new PNM';
        return view('admin.newPNM', $data);
    }

    public function getLGAs(Request $request)
    {
        $state = $request->input('state');
        $lgas = $this->LGAs();
        $html = "<option selected disabled>Select LGA</option>";
        foreach ($lgas as $lga) {
            if ($lga[0] == $state) {
                $html .= "<option>$lga[1]</option>";
            }
        }
        return response()->json([
            'html' => $html
        ]);
    }

    public function LGAs()
    {
        $states = [
            ["Abia", "Aba North"],
            ["Abia", "Aba South"],
            ["Abia", "Arochukwu"],
            ["Abia", "Bende"],
            ["Abia", "Ikwuano"],
            ["Abia", "Isiala Ngwa North"],
            ["Abia", "Isiala Ngwa South"],
            ["Abia", "Isuikwuato"],
            ["Abia", "Obi Ngwa"],
            ["Abia", "Ohafia"],
            ["Abia", "Osisioma"],
            ["Abia", "Ugwunagbo"],
            ["Abia", "Ukwa East"],
            ["Abia", "Ukwa West"],
            ["Abia", "Umuahia North"],
            ["Abia", "Umuahia South"],
            ["Abia", "Umu Nneochi"],
            ["Abia", "Obi Ngwa"],
            ["Adamawa", "Demsa"],
            ["Adamawa", "Fufure"],
            ["Adamawa", "Ganye"],
            ["Adamawa", "Gayuk"],
            ["Adamawa", "Gombi"],
            ["Adamawa", "Grie"],
            ["Adamawa", "Hong"],
            ["Adamawa", "Jada"],
            ["Adamawa", "Lamurde"],
            ["Adamawa", "Madagali"],
            ["Adamawa", "Maiha"],
            ["Adamawa", "Mayo Belwa"],
            ["Adamawa", "Michika"],
            ["Adamawa", "Mubi North"],
            ["Adamawa", "Mubi South"],
            ["Adamawa", "Numan"],
            ["Adamawa", "Shelleng"],
            ["Adamawa", "Song"],
            ["Adamawa", "Toungo"],
            ["Adamawa", "Yola North"],
            ["Adamawa", "Yola South"],
            ["Akwa Ibom", "Abak"],
            ["Akwa Ibom", "Eastern Obolo"],
            ["Akwa Ibom", "Eket"],
            ["Akwa Ibom", "Esit Eket"],
            ["Akwa Ibom", "Essien Udim"],
            ["Akwa Ibom", "Etim Ekpo"],
            ["Akwa Ibom", "Etinan"],
            ["Akwa Ibom", "Ibeno"],
            ["Akwa Ibom", "Ibesikpo Asutan"],
            ["Akwa Ibom", "Ibiono-Ibom"],
            ["Akwa Ibom", "Ika"],
            ["Akwa Ibom", "Ikono"],
            ["Akwa Ibom", "Ikot Abasi"],
            ["Akwa Ibom", "Ikot Ekpene"],
            ["Akwa Ibom", "Ini"],
            ["Akwa Ibom", "Itu"],
            ["Akwa Ibom", "Mbo"],
            ["Akwa Ibom", "Mkpat-Enin"],
            ["Akwa Ibom", "Nsit-Atai"],
            ["Akwa Ibom", "Nsit-Ibom"],
            ["Akwa Ibom", "Nsit-Ubium"],
            ["Akwa Ibom", "Obot Akara"],
            ["Akwa Ibom", "Okobo"],
            ["Akwa Ibom", "Onna"],
            ["Akwa Ibom", "Oron"],
            ["Akwa Ibom", "Oruk Anam"],
            ["Akwa Ibom", "Udung-Uko"],
            ["Akwa Ibom", "Ukanafun"],
            ["Akwa Ibom", "Uruan"],
            ["Akwa Ibom", "Urue-Offong/Oruko"],
            ["Akwa Ibom", "Uyo"],
            ["Akwa Ibom", "Obot Akara"],
            ["Anambra", "Aguata"],
            ["Anambra", "Anambra East"],
            ["Anambra", "Anambra West"],
            ["Anambra", "Anaocha"],
            ["Anambra", "Awka North"],
            ["Anambra", "Awka South"],
            ["Anambra", "Ayamelum"],
            ["Anambra", "Dunukofia"],
            ["Anambra", "Ekwusigo"],
            ["Anambra", "Idemili North"],
            ["Anambra", "Idemili South"],
            ["Anambra", "Ihiala"],
            ["Anambra", "Njikoka"],
            ["Anambra", "Nnewi North"],
            ["Anambra", "Nnewi South"],
            ["Anambra", "Ogbaru"],
            ["Anambra", "Onitsha North"],
            ["Anambra", "Onitsha South"],
            ["Anambra", "Orumba North"],
            ["Anambra", "Orumba South"],
            ["Anambra", "Oyi"],
            ["Bauchi", "Alkaleri"],
            ["Bauchi", "Bauchi"],
            ["Bauchi", "Bogoro"],
            ["Bauchi", "Damban"],
            ["Bauchi", "Darazo"],
            ["Bauchi", "Dass"],
            ["Bauchi", "Gamawa"],
            ["Bauchi", "Ganjuwa"],
            ["Bauchi", "Giade"],
            ["Bauchi", "Itas/Gadau"],
            ["Bauchi", "Jama'are"],
            ["Bauchi", "Katagum"],
            ["Bauchi", "Kirfi"],
            ["Bauchi", "Misau"],
            ["Bauchi", "Ningi"],
            ["Bauchi", "Shira"],
            ["Bauchi", "Tafawa Balewa"],
            ["Bauchi", "Toro"],
            ["Bauchi", "Warji"],
            ["Bauchi", "Zaki"],
            ["Bayelsa", "Brass"],
            ["Bayelsa", "Ekeremor"],
            ["Bayelsa", "Kolokuma/Opokuma"],
            ["Bayelsa", "Nembe"],
            ["Bayelsa", "Ogbia"],
            ["Bayelsa", "Sagbama"],
            ["Bayelsa", "Southern Ijaw"],
            ["Bayelsa", "Yenagoa"],
            ["Benue", "Agatu"],
            ["Benue", "Apa"],
            ["Benue", "Ado"],
            ["Benue", "Buruku"],
            ["Benue", "Gboko"],
            ["Benue", "Guma"],
            ["Benue", "Gwer East"],
            ["Benue", "Gwer West"],
            ["Benue", "Katsina-Ala"],
            ["Benue", "Konshisha"],
            ["Benue", "Kwande"],
            ["Benue", "Logo"],
            ["Benue", "Makurdi"],
            ["Benue", "Obi"],
            ["Benue", "Ogbadibo"],
            ["Benue", "Ohimini"],
            ["Benue", "Oju"],
            ["Benue", "Okpokwu"],
            ["Benue", "Oturkpo"],
            ["Benue", "Tarka"],
            ["Benue", "Ukum"],
            ["Benue", "Ushongo"],
            ["Benue", "Vandeikya"],
            ["Benue", "Obi"],
            ["Borno", "Abadam"],
            ["Borno", "Askira/Uba"],
            ["Borno", "Bama"],
            ["Borno", "Bayo"],
            ["Borno", "Biu"],
            ["Borno", "Chibok"],
            ["Borno", "Damboa"],
            ["Borno", "Dikwa"],
            ["Borno", "Gubio"],
            ["Borno", "Guzamala"],
            ["Borno", "Gwoza"],
            ["Borno", "Hawul"],
            ["Borno", "Jere"],
            ["Borno", "Kaga"],
            ["Borno", "Kala/Balge"],
            ["Borno", "Konduga"],
            ["Borno", "Kukawa"],
            ["Borno", "Kwaya Kusar"],
            ["Borno", "Mafa"],
            ["Borno", "Magumeri"],
            ["Borno", "Maiduguri"],
            ["Borno", "Marte"],
            ["Borno", "Mobbar"],
            ["Borno", "Monguno"],
            ["Borno", "Ngala"],
            ["Borno", "Nganzai"],
            ["Borno", "Shani"],
            ["Cross River", "Abi"],
            ["Cross River", "Akamkpa"],
            ["Cross River", "Akpabuyo"],
            ["Cross River", "Bakassi"],
            ["Cross River", "Bekwarra"],
            ["Cross River", "Biase"],
            ["Cross River", "Boki"],
            ["Cross River", "Calabar Municipal"],
            ["Cross River", "Calabar South"],
            ["Cross River", "Etung"],
            ["Cross River", "Ikom"],
            ["Cross River", "Obanliku"],
            ["Cross River", "Obubra"],
            ["Cross River", "Obudu"],
            ["Cross River", "Odukpani"],
            ["Cross River", "Ogoja"],
            ["Cross River", "Yakuur"],
            ["Cross River", "Obubra"],
            ["Cross River", "Yala"],
            ["Delta", "Aniocha North"],
            ["Delta", "Aniocha South"],
            ["Delta", "Bomadi"],
            ["Delta", "Burutu"],
            ["Delta", "Ethiope East"],
            ["Delta", "Ethiope West"],
            ["Delta", "Ika North East"],
            ["Delta", "Ika South"],
            ["Delta", "Isoko North"],
            ["Delta", "Isoko South"],
            ["Delta", "Ndokwa East"],
            ["Delta", "Ndokwa West"],
            ["Delta", "Okpe"],
            ["Delta", "Oshimili North"],
            ["Delta", "Oshimili South"],
            ["Delta", "Patani"],
            ["Delta", "Sapele"],
            ["Delta", "Udu"],
            ["Delta", "Ughelli North"],
            ["Delta", "Ughelli South"],
            ["Delta", "Ukwuani"],
            ["Delta", "Uvwie"],
            ["Delta", "Warri North"],
            ["Delta", "Warri South"],
            ["Delta", "Warri South West"],
            ["Ebonyi", "Abakaliki"],
            ["Ebonyi", "Afikpo North"],
            ["Ebonyi", "Afikpo South (Edda)"],
            ["Ebonyi", "Ebonyi"],
            ["Ebonyi", "Ezza North"],
            ["Ebonyi", "Ezza South"],
            ["Ebonyi", "Ikwo"],
            ["Ebonyi", "Ishielu"],
            ["Ebonyi", "Ivo"],
            ["Ebonyi", "Izzi"],
            ["Ebonyi", "Ohaozara"],
            ["Ebonyi", "Ohaukwu"],
            ["Ebonyi", "Onicha"],
            ["Edo", "Akoko-Edo"],
            ["Edo", "Egor"],
            ["Edo", "Esan Central"],
            ["Edo", "Esan North-East"],
            ["Edo", "Esan South-East"],
            ["Edo", "Esan West"],
            ["Edo", "Etsako Central"],
            ["Edo", "Etsako East"],
            ["Edo", "Etsako West"],
            ["Edo", "Igueben"],
            ["Edo", "Ikpoba Okha"],
            ["Edo", "Orhionmwon"],
            ["Edo", "Oredo"],
            ["Edo", "Ovia North-East"],
            ["Edo", "Ovia South-West"],
            ["Edo", "Owan East"],
            ["Edo", "Owan West"],
            ["Edo", "Uhunmwonde"],
            ["Ekiti", "Ado Ekiti"],
            ["Ekiti", "Efon"],
            ["Ekiti", "Ekiti East"],
            ["Ekiti", "Ekiti South-West"],
            ["Ekiti", "Ekiti West"],
            ["Ekiti", "Emure"],
            ["Ekiti", "Gbonyin"],
            ["Ekiti", "Ido Osi"],
            ["Ekiti", "Ijero"],
            ["Ekiti", "Ikere"],
            ["Ekiti", "Ikole"],
            ["Ekiti", "Ilejemeje"],
            ["Ekiti", "Irepodun/Ifelodun"],
            ["Ekiti", "Ise/Orun"],
            ["Ekiti", "Moba"],
            ["Ekiti", "Oye"],
            ["Enugu", "Aninri"],
            ["Enugu", "Awgu"],
            ["Enugu", "Enugu East"],
            ["Enugu", "Enugu North"],
            ["Enugu", "Enugu South"],
            ["Enugu", "Ezeagu"],
            ["Enugu", "Igbo Etiti"],
            ["Enugu", "Igbo Eze North"],
            ["Enugu", "Igbo Eze South"],
            ["Enugu", "Isi Uzo"],
            ["Enugu", "Nkanu East"],
            ["Enugu", "Nkanu West"],
            ["Enugu", "Nsukka"],
            ["Enugu", "Oji River"],
            ["Enugu", "Udenu"],
            ["Enugu", "Udi"],
            ["Enugu", "Uzo-Uwani"],
            ["FCT", "Abaji"],
            ["FCT", "Bwari"],
            ["FCT", "Gwagwalada"],
            ["FCT", "Kuje"],
            ["FCT", "Kwali"],
            ["FCT", "Municipal Area Council"],
            ["Gombe", "Akko"],
            ["Gombe", "Balanga"],
            ["Gombe", "Billiri"],
            ["Gombe", "Dukku"],
            ["Gombe", "Funakaye"],
            ["Gombe", "Gombe"],
            ["Gombe", "Kaltungo"],
            ["Gombe", "Kwami"],
            ["Gombe", "Nafada"],
            ["Gombe", "Shongom"],
            ["Gombe", "Yamaltu/Deba"],
            ["Imo", "Aboh Mbaise"],
            ["Imo", "Ahiazu Mbaise"],
            ["Imo", "Ehime Mbano"],
            ["Imo", "Ezinihitte"],
            ["Imo", "Ideato North"],
            ["Imo", "Ideato South"],
            ["Imo", "Ihitte/Uboma"],
            ["Imo", "Ikeduru"],
            ["Imo", "Isiala Mbano"],
            ["Imo", "Isu"],
            ["Imo", "Mbaitoli"],
            ["Imo", "Ngor Okpala"],
            ["Imo", "Njaba"],
            ["Imo", "Nkwerre"],
            ["Imo", "Nwangele"],
            ["Imo", "Obowo"],
            ["Imo", "Oguta"],
            ["Imo", "Ohaji/Egbema"],
            ["Imo", "Okigwe"],
            ["Imo", "Orlu"],
            ["Imo", "Orsu"],
            ["Imo", "Oru East"],
            ["Imo", "Oru West"],
            ["Imo", "Owerri Municipal"],
            ["Imo", "Owerri North"],
            ["Imo", "Owerri West"],
            ["Imo", "Unuimo"],
            ["Imo", "Obowo"],
            ["Jigawa", "Auyo"],
            ["Jigawa", "Babura"],
            ["Jigawa", "Biriniwa"],
            ["Jigawa", "Birnin Kudu"],
            ["Jigawa", "Buji"],
            ["Jigawa", "Dutse"],
            ["Jigawa", "Gagarawa"],
            ["Jigawa", "Garki"],
            ["Jigawa", "Gumel"],
            ["Jigawa", "Guri"],
            ["Jigawa", "Gwaram"],
            ["Jigawa", "Gwiwa"],
            ["Jigawa", "Hadejia"],
            ["Jigawa", "Jahun"],
            ["Jigawa", "Kafin Hausa"],
            ["Jigawa", "Kazaure"],
            ["Jigawa", "Kiri Kasama"],
            ["Jigawa", "Kiyawa"],
            ["Jigawa", "Kaugama"],
            ["Jigawa", "Maigatari"],
            ["Jigawa", "Malam Madori"],
            ["Jigawa", "Miga"],
            ["Jigawa", "Ringim"],
            ["Jigawa", "Roni"],
            ["Jigawa", "Sule Tankarkar"],
            ["Jigawa", "Taura"],
            ["Jigawa", "Yankwashi"],
            ["Kaduna", "Birnin Gwari"],
            ["Kaduna", "Chikun"],
            ["Kaduna", "Giwa"],
            ["Kaduna", "Igabi"],
            ["Kaduna", "Ikara"],
            ["Kaduna", "Jaba"],
            ["Kaduna", "Jema'a"],
            ["Kaduna", "Kachia"],
            ["Kaduna", "Kaduna North"],
            ["Kaduna", "Kaduna South"],
            ["Kaduna", "Kagarko"],
            ["Kaduna", "Kajuru"],
            ["Kaduna", "Kaura"],
            ["Kaduna", "Kauru"],
            ["Kaduna", "Kubau"],
            ["Kaduna", "Kudan"],
            ["Kaduna", "Lere"],
            ["Kaduna", "Makarfi"],
            ["Kaduna", "Sabon Gari"],
            ["Kaduna", "Sanga"],
            ["Kaduna", "Soba"],
            ["Kaduna", "Zangon Kataf"],
            ["Kaduna", "Zaria"],
            ["Kano", "Ajingi"],
            ["Kano", "Albasu"],
            ["Kano", "Bagwai"],
            ["Kano", "Bebeji"],
            ["Kano", "Bichi"],
            ["Kano", "Bunkure"],
            ["Kano", "Dala"],
            ["Kano", "Dambatta"],
            ["Kano", "Dawakin Kudu"],
            ["Kano", "Dawakin Tofa"],
            ["Kano", "Doguwa"],
            ["Kano", "Fagge"],
            ["Kano", "Gabasawa"],
            ["Kano", "Garko"],
            ["Kano", "Garun Mallam"],
            ["Kano", "Gaya"],
            ["Kano", "Gezawa"],
            ["Kano", "Gwale"],
            ["Kano", "Gwarzo"],
            ["Kano", "Kabo"],
            ["Kano", "Kano Municipal"],
            ["Kano", "Karaye"],
            ["Kano", "Kibiya"],
            ["Kano", "Kiru"],
            ["Kano", "Kumbotso"],
            ["Kano", "Kunchi"],
            ["Kano", "Kura"],
            ["Kano", "Madobi"],
            ["Kano", "Makoda"],
            ["Kano", "Minjibir"],
            ["Kano", "Nasarawa"],
            ["Kano", "Rano"],
            ["Kano", "Rimin Gado"],
            ["Kano", "Rogo"],
            ["Kano", "Shanono"],
            ["Kano", "Sumaila"],
            ["Kano", "Takai"],
            ["Kano", "Tarauni"],
            ["Kano", "Tofa"],
            ["Kano", "Tsanyawa"],
            ["Kano", "Tudun Wada"],
            ["Kano", "Ungogo"],
            ["Kano", "Warawa"],
            ["Kano", "Wudil"],
            ["Katsina", "Bakori"],
            ["Katsina", "Batagarawa"],
            ["Katsina", "Batsari"],
            ["Katsina", "Baure"],
            ["Katsina", "Bindawa"],
            ["Katsina", "Charanchi"],
            ["Katsina", "Dandume"],
            ["Katsina", "Danja"],
            ["Katsina", "Dan Musa"],
            ["Katsina", "Daura"],
            ["Katsina", "Dutsi"],
            ["Katsina", "Dutsin Ma"],
            ["Katsina", "Faskari"],
            ["Katsina", "Funtua"],
            ["Katsina", "Ingawa"],
            ["Katsina", "Jibia"],
            ["Katsina", "Kafur"],
            ["Katsina", "Kaita"],
            ["Katsina", "Kankara"],
            ["Katsina", "Kankia"],
            ["Katsina", "Katsina"],
            ["Katsina", "Kurfi"],
            ["Katsina", "Kusada"],
            ["Katsina", "Mai'Adua"],
            ["Katsina", "Malumfashi"],
            ["Katsina", "Mani"],
            ["Katsina", "Mashi"],
            ["Katsina", "Matazu"],
            ["Katsina", "Musawa"],
            ["Katsina", "Rimi"],
            ["Katsina", "Sabuwa"],
            ["Katsina", "Safana"],
            ["Katsina", "Sandamu"],
            ["Katsina", "Zango"],
            ["Kebbi", "Aleiro"],
            ["Kebbi", "Arewa Dandi"],
            ["Kebbi", "Argungu"],
            ["Kebbi", "Augie"],
            ["Kebbi", "Bagudo"],
            ["Kebbi", "Birnin Kebbi"],
            ["Kebbi", "Bunza"],
            ["Kebbi", "Dandi"],
            ["Kebbi", "Fakai"],
            ["Kebbi", "Gwandu"],
            ["Kebbi", "Jega"],
            ["Kebbi", "Kalgo"],
            ["Kebbi", "Koko/Besse"],
            ["Kebbi", "Maiyama"],
            ["Kebbi", "Ngaski"],
            ["Kebbi", "Sakaba"],
            ["Kebbi", "Shanga"],
            ["Kebbi", "Suru"],
            ["Kebbi", "Wasagu/Danko"],
            ["Kebbi", "Yauri"],
            ["Kebbi", "Zuru"],
            ["Kogi", "Adavi"],
            ["Kogi", "Ajaokuta"],
            ["Kogi", "Ankpa"],
            ["Kogi", "Bassa"],
            ["Kogi", "Dekina"],
            ["Kogi", "Ibaji"],
            ["Kogi", "Idah"],
            ["Kogi", "Igalamela Odolu"],
            ["Kogi", "Ijumu"],
            ["Kogi", "Kabba/Bunu"],
            ["Kogi", "Kogi"],
            ["Kogi", "Lokoja"],
            ["Kogi", "Mopa Muro"],
            ["Kogi", "Ofu"],
            ["Kogi", "Ogori/Magongo"],
            ["Kogi", "Okehi"],
            ["Kogi", "Okene"],
            ["Kogi", "Olamaboro"],
            ["Kogi", "Omala"],
            ["Kogi", "Yagba East"],
            ["Kogi", "Yagba West"],
            ["Kwara", "Asa"],
            ["Kwara", "Baruten"],
            ["Kwara", "Edu"],
            ["Kwara", "Ekiti"],
            ["Kwara", "Ifelodun"],
            ["Kwara", "Ilorin East"],
            ["Kwara", "Ilorin South"],
            ["Kwara", "Ilorin West"],
            ["Kwara", "Irepodun"],
            ["Kwara", "Isin"],
            ["Kwara", "Kaiama"],
            ["Kwara", "Moro"],
            ["Kwara", "Offa"],
            ["Kwara", "Oke Ero"],
            ["Kwara", "Oyun"],
            ["Kwara", "Pategi"],
            ["Lagos", "Agege"],
            ["Lagos", "Ajeromi-Ifelodun"],
            ["Lagos", "Alimosho"],
            ["Lagos", "Amuwo-Odofin"],
            ["Lagos", "Apapa"],
            ["Lagos", "Badagry"],
            ["Lagos", "Epe"],
            ["Lagos", "Eti Osa"],
            ["Lagos", "Ibeju-Lekki"],
            ["Lagos", "Ifako-Ijaiye"],
            ["Lagos", "Ikeja"],
            ["Lagos", "Ikorodu"],
            ["Lagos", "Kosofe"],
            ["Lagos", "Lagos Island"],
            ["Lagos", "Lagos Mainland"],
            ["Lagos", "Mushin"],
            ["Lagos", "Ojo"],
            ["Lagos", "Oshodi-Isolo"],
            ["Lagos", "Shomolu"],
            ["Lagos", "Surulere"],
            ["Nasarawa", "Akwanga"],
            ["Nasarawa", "Awe"],
            ["Nasarawa", "Doma"],
            ["Nasarawa", "Karu"],
            ["Nasarawa", "Keana"],
            ["Nasarawa", "Keffi"],
            ["Nasarawa", "Kokona"],
            ["Nasarawa", "Lafia"],
            ["Nasarawa", "Nasarawa"],
            ["Nasarawa", "Nasarawa Egon"],
            ["Nasarawa", "Obi"],
            ["Nasarawa", "Toto"],
            ["Nasarawa", "Wamba"],
            ["Nasarawa", "Obi"],
            ["Niger", "Agaie"],
            ["Niger", "Agwara"],
            ["Niger", "Bida"],
            ["Niger", "Borgu"],
            ["Niger", "Bosso"],
            ["Niger", "Chanchaga"],
            ["Niger", "Edati"],
            ["Niger", "Gbako"],
            ["Niger", "Gurara"],
            ["Niger", "Katcha"],
            ["Niger", "Kontagora"],
            ["Niger", "Lapai"],
            ["Niger", "Lavun"],
            ["Niger", "Magama"],
            ["Niger", "Mariga"],
            ["Niger", "Mashegu"],
            ["Niger", "Mokwa"],
            ["Niger", "Moya"],
            ["Niger", "Paikoro"],
            ["Niger", "Rafi"],
            ["Niger", "Rijau"],
            ["Niger", "Shiroro"],
            ["Niger", "Suleja"],
            ["Niger", "Tafa"],
            ["Niger", "Wushishi"],
            ["Ogun", "Abeokuta North"],
            ["Ogun", "Abeokuta South"],
            ["Ogun", "Ado-Odo/Ota"],
            ["Ogun", "Yewa North"],
            ["Ogun", "Yewa South"],
            ["Ogun", "Ewekoro"],
            ["Ogun", "Ifo"],
            ["Ogun", "Ijebu East"],
            ["Ogun", "Ijebu North"],
            ["Ogun", "Ijebu North East"],
            ["Ogun", "Ijebu Ode"],
            ["Ogun", "Ikenne"],
            ["Ogun", "Imeko Afon"],
            ["Ogun", "Ipokia"],
            ["Ogun", "Obafemi Owode"],
            ["Ogun", "Odeda"],
            ["Ogun", "Odogbolu"],
            ["Ogun", "Ogun Waterside"],
            ["Ogun", "Remo North"],
            ["Ogun", "Shagamu"],
            ["Ondo", "Akoko North-East"],
            ["Ondo", "Akoko North-West"],
            ["Ondo", "Akoko South-West"],
            ["Ondo", "Akoko South-East"],
            ["Ondo", "Akure North"],
            ["Ondo", "Akure South"],
            ["Ondo", "Ese Odo"],
            ["Ondo", "Idanre"],
            ["Ondo", "Ifedore"],
            ["Ondo", "Ilaje"],
            ["Ondo", "Ile Oluji/Okeigbo"],
            ["Ondo", "Irele"],
            ["Ondo", "Odigbo"],
            ["Ondo", "Okitipupa"],
            ["Ondo", "Ondo East"],
            ["Ondo", "Ondo West"],
            ["Ondo", "Ose"],
            ["Ondo", "Owo"],
            ["Osun", "Atakunmosa East"],
            ["Osun", "Atakunmosa West"],
            ["Osun", "Aiyedaade"],
            ["Osun", "Aiyedire"],
            ["Osun", "Boluwaduro"],
            ["Osun", "Boripe"],
            ["Osun", "Ede North"],
            ["Osun", "Ede South"],
            ["Osun", "Ife Central"],
            ["Osun", "Ife East"],
            ["Osun", "Ife North"],
            ["Osun", "Ife South"],
            ["Osun", "Egbedore"],
            ["Osun", "Ejigbo"],
            ["Osun", "Ifedayo"],
            ["Osun", "Ifelodun"],
            ["Osun", "Ila"],
            ["Osun", "Ilesa East"],
            ["Osun", "Ilesa West"],
            ["Osun", "Irepodun"],
            ["Osun", "Irewole"],
            ["Osun", "Isokan"],
            ["Osun", "Iwo"],
            ["Osun", "Obokun"],
            ["Osun", "Odo Otin"],
            ["Osun", "Ola Oluwa"],
            ["Osun", "Olorunda"],
            ["Osun", "Oriade"],
            ["Osun", "Orolu"],
            ["Osun", "Osogbo"],
            ["Osun", "Obokun"],
            ["Oyo", "Afijio"],
            ["Oyo", "Akinyele"],
            ["Oyo", "Atiba"],
            ["Oyo", "Atisbo"],
            ["Oyo", "Egbeda"],
            ["Oyo", "Ibadan North"],
            ["Oyo", "Ibadan North-East"],
            ["Oyo", "Ibadan North-West"],
            ["Oyo", "Ibadan South-East"],
            ["Oyo", "Ibadan South-West"],
            ["Oyo", "Ibarapa Central"],
            ["Oyo", "Ibarapa East"],
            ["Oyo", "Ibarapa North"],
            ["Oyo", "Ido"],
            ["Oyo", "Irepo"],
            ["Oyo", "Iseyin"],
            ["Oyo", "Itesiwaju"],
            ["Oyo", "Iwajowa"],
            ["Oyo", "Kajola"],
            ["Oyo", "Lagelu"],
            ["Oyo", "Ogbomosho North"],
            ["Oyo", "Ogbomosho South"],
            ["Oyo", "Ogo Oluwa"],
            ["Oyo", "Olorunsogo"],
            ["Oyo", "Oluyole"],
            ["Oyo", "Ona Ara"],
            ["Oyo", "Orelope"],
            ["Oyo", "Ori Ire"],
            ["Oyo", "Oyo West"],
            ["Oyo", "Oyo East"],
            ["Oyo", "Saki East"],
            ["Oyo", "Saki West"],
            ["Oyo", "Surulere"],
            ["Plateau", "Bokkos"],
            ["Plateau", "Barkin Ladi"],
            ["Plateau", "Bassa"],
            ["Plateau", "Jos East"],
            ["Plateau", "Jos North"],
            ["Plateau", "Jos South"],
            ["Plateau", "Kanam"],
            ["Plateau", "Kanke"],
            ["Plateau", "Langtang South"],
            ["Plateau", "Langtang North"],
            ["Plateau", "Mangu"],
            ["Plateau", "Mikang"],
            ["Plateau", "Pankshin"],
            ["Plateau", "Qua'an Pan"],
            ["Plateau", "Riyom"],
            ["Plateau", "Shendam"],
            ["Plateau", "Wase"],
            ["Rivers", "Abua/Odual"],
            ["Rivers", "Ahoada East"],
            ["Rivers", "Ahoada West"],
            ["Rivers", "Akuku-Toru"],
            ["Rivers", "Andoni"],
            ["Rivers", "Asari-Toru"],
            ["Rivers", "Bonny"],
            ["Rivers", "Degema"],
            ["Rivers", "Eleme"],
            ["Rivers", "Emuoha"],
            ["Rivers", "Etche"],
            ["Rivers", "Gokana"],
            ["Rivers", "Ikwerre"],
            ["Rivers", "Khana"],
            ["Rivers", "Obio/Akpor"],
            ["Rivers", "Ogba/Egbema/Ndoni"],
            ["Rivers", "Ogu/Bolo"],
            ["Rivers", "Okrika"],
            ["Rivers", "Omuma"],
            ["Rivers", "Opobo/Nkoro"],
            ["Rivers", "Oyigbo"],
            ["Rivers", "Port Harcourt"],
            ["Rivers", "Tai"],
            ["Rivers", "Obio/Akpor"],
            ["Sokoto", "Binji"],
            ["Sokoto", "Bodinga"],
            ["Sokoto", "Dange Shuni"],
            ["Sokoto", "Gada"],
            ["Sokoto", "Goronyo"],
            ["Sokoto", "Gudu"],
            ["Sokoto", "Gwadabawa"],
            ["Sokoto", "Illela"],
            ["Sokoto", "Isa"],
            ["Sokoto", "Kebbe"],
            ["Sokoto", "Kware"],
            ["Sokoto", "Rabah"],
            ["Sokoto", "Sabon Birni"],
            ["Sokoto", "Shagari"],
            ["Sokoto", "Silame"],
            ["Sokoto", "Sokoto North"],
            ["Sokoto", "Sokoto South"],
            ["Sokoto", "Tambuwal"],
            ["Sokoto", "Tangaza"],
            ["Sokoto", "Tureta"],
            ["Sokoto", "Wamako"],
            ["Sokoto", "Wurno"],
            ["Sokoto", "Yabo"],
            ["Taraba", "Ardo Kola"],
            ["Taraba", "Bali"],
            ["Taraba", "Donga"],
            ["Taraba", "Gashaka"],
            ["Taraba", "Gassol"],
            ["Taraba", "Ibi"],
            ["Taraba", "Jalingo"],
            ["Taraba", "Karim Lamido"],
            ["Taraba", "Kumi"],
            ["Taraba", "Lau"],
            ["Taraba", "Sardauna"],
            ["Taraba", "Takum"],
            ["Taraba", "Ussa"],
            ["Taraba", "Wukari"],
            ["Taraba", "Yorro"],
            ["Taraba", "Zing"],
            ["Yobe", "Bade"],
            ["Yobe", "Bursari"],
            ["Yobe", "Damaturu"],
            ["Yobe", "Fika"],
            ["Yobe", "Fune"],
            ["Yobe", "Geidam"],
            ["Yobe", "Gujba"],
            ["Yobe", "Gulani"],
            ["Yobe", "Jakusko"],
            ["Yobe", "Karasuwa"],
            ["Yobe", "Machina"],
            ["Yobe", "Nangere"],
            ["Yobe", "Nguru"],
            ["Yobe", "Potiskum"],
            ["Yobe", "Tarmuwa"],
            ["Yobe", "Yunusari"],
            ["Yobe", "Yusufari"],
            ["Zamfara", "Anka"],
            ["Zamfara", "Bakura"],
            ["Zamfara", "Birnin Magaji/Kiyaw"],
            ["Zamfara", "Bukkuyum"],
            ["Zamfara", "Bungudu"],
            ["Zamfara", "Gummi"],
            ["Zamfara", "Gusau"],
            ["Zamfara", "Kaura Namoda"],
            ["Zamfara", "Maradun"],
            ["Zamfara", "Maru"],
            ["Zamfara", "Shinkafi"],
            ["Zamfara", "Talata Mafara"],
            ["Zamfara", "Chafe"],
            ["Zamfara", "Zurmi"]
        ];
        return $states;
    }
}
