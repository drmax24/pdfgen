<?php
namespace App\Http\Controllers;

use App\Mail\PdfMail;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Request;
use mikehaertl\wkhtmlto\Pdf;

class UriToPdf extends Controller
{
    public $secureDomanList = [
        'bstd.ru',
        'toyota.ru',
        'lexus.ru',
        'audi.ru',
        'obi.ru',
        'obiclub.ru'
    ];

    public function __construct()
    {
        ini_set('max_execution_time', 11);
        ini_set('memory_limit', "256M");
    }





    public function getPdf()
    {
        $pdfFileName = '';

        $qs = Request::getQueryString();
        $qsDecoded = base64_decode($qs);
        if (base64_encode($qsDecoded) === $qs){
            parse_str($qsDecoded,$input);
            dd($input);
        }

        if (!Input::has('target_uri')) {
            return json_response([
                'status' => 'Укажите параметр target_uri'
            ], 400);
        }

        if (!$this->isSecureDomain(Input::get('target_uri'))) {
            return json_response([
                'status' => 'Forbiddent target: ' . Input::get('target_uri')
            ], 404);
        }

        if (Input::has('pdf_file_name')) {
            $pdfFileName = Input::get('pdf_file_name');
            $ext         = strtolower(pathinfo($pdfFileName, PATHINFO_EXTENSION));
            if ($ext != 'pdf') {
                $pdfFileName .= '.pdf';
            }
        }


        //parse_url(Input::get('target_uri'));

        $input     = Input::all();
        $targetUri = $input['target_uri'];



        $pdfConfig = $this->initPdfConfig($input);
        $pdf = new Pdf($pdfConfig);


        // Строим гет-запрос на страницу снимок которой будем делать
        unset($input['target_url'], $input['wkhtmltopdf-params']);
        unset($input['email']);
        $query = http_build_query($input);
        $uri = $targetUri . '?' . $query;

        $pdf->addPage($uri);

        // сохраняем пдф-файл в переменную
        $pdfData = $pdf->toString();
        if ($pdfData) {

        } else {
            echo $pdf->getError();
        }



        if (Input::get('email.to')) {

            $validator = \Validator::make(
                Input::all(), [
                    'email.from'    => 'required|email',
                    'email.to'      => 'required',
                    'email.subject' => 'required',
                    'email.body'    => 'required',
                    'pdf_file_name' => 'required',
                ]
            );


            if ($validator->fails()) {
                return json_response(['status' => 'error', 'explanation' => $validator->errors()],
                    $status = 400,
                    ['Content-Type' => 'application/json; charset=UTF-8']);
            }



            Mail
                ::to(Input::get('email.to'))
                ->queue(new PdfMail(
                    Input::get('email.from'),
                    Input::get('email.subject'),
                    Input::get('email.body'),
                    Input::get('pdf_file_name'),
                    base64_encode($pdfData)));
            app('sentry')->captureMessage('Письмо добавлено в очередь: ' . print_r(Input::get('email.to'), true), [],
                ['level' => 'info']);
        } else {
            if ($pdfFileName && Input::get('open_file_in_browser')) {

                $pdf->send($pdfFileName, true);
                exit;


            } elseif ($pdfFileName) {
                // Открыть в браузере с указанием имени файла

                $pdf->send($pdfFileName);
                exit;
            } else {
                // Открыть в браузере без указания имени файла
                $pdf->send();
                exit;
            }
        }

        return json_response(['status' => 'ok']);

    }

    public function initPdfConfig($input){
        $wkhtmltopdfParams = $input['wkhtmltopdf-params']??null;

        unset($wkhtmltopdfParams['binary']);

        $params = [
            'binary' => '/usr/local/bin/wkhtmltopdf',
            //'javascript-delay' => '4000',
            //'window-status'    => 'ready-to-print'
        ];

        //http://pdfgen.microservices.local/api/uri-to-pdf?target_uri=http://toyota-tech-service.coding.dev.bstd.ru/index1.html&wkhtmltopdf-params[javascript-delay]=4000

        if ($wkhtmltopdfParams) {
            foreach ($wkhtmltopdfParams as $k => $v) {
                $params[$k] = $v;
            }
        }

        return $params;
    }


    public function isSecureDomain($uri)
    {
        $parsedUrl = parse_url($uri);
        foreach ($this->secureDomanList as $secureDomain) {
            if (substr($parsedUrl['host'], -strlen($secureDomain)) === $secureDomain) {
                return true;
            }
        }
    }
}
