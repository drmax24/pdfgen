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
        $isBase64 = false;


        $qs = urldecode(Request::getQueryString());
        $qsDecoded = base64_decode($qs);
        if ($qs && base64_encode($qsDecoded) === $qs){
            parse_str($qsDecoded,$input);
            $isBase64 = 1;
        } else {
            $qs = str_replace(' ', '+', $qs);
            $qsDecoded = base64_decode($qs);
            
            if ($qs && base64_encode($qsDecoded) === $qs){
                parse_str($qsDecoded,$input);
                $isBase64 = 1;
            } else {
                $input          = Request::all();
            }
        }

        $inputSanitized = $input;

        if (!isset($input['target_uri'])) {
            return json_response([
                'status' => 'Укажите параметр target_uri.',
                'isBase64' => $isBase64,
                'input' => $input,
                'qs' => $qs
            ], 400);
        }

        if (!$this->isSecureDomain($input['target_uri'])) {
            return json_response([
                'status' => 'Forbidden target: ' . $input('target_uri')
            ], 404);
        }

        if (isset($input['pdf_file_name'])) {
            $pdfFileName = $input['pdf_file_name'];
            $ext         = strtolower(pathinfo($pdfFileName, PATHINFO_EXTENSION));
            if ($ext != 'pdf') {
                $pdfFileName .= '.pdf';
            }
        }


        $pdfConfig = $this->initPdfConfig($input);
        $pdf = new Pdf($pdfConfig);


        // Строим гет-запрос на страницу снимок которой будем делать
        unset($inputSanitized['target_url'], $inputSanitized['wkhtmltopdf-params']);
        unset($inputSanitized['email']);
        $query = http_build_query($inputSanitized);
        $uri = $input['target_uri'] . '?' . $query;

        $pdf->addPage($uri);

        // сохраняем пдф-файл в переменную
        $pdfData = $pdf->toString();
        if ($pdfData) {

        } else {
            echo $pdf->getError();
        }


        if (isset($input['email']['to'])) {
            $validator = \Validator::make(
                $input, [
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
                ::to($input['email']['to'])
                ->queue(new PdfMail(
                    $input['email']['from'],
                    $input['email']['subject'],
                    $input['email']['body'],
                    $input['pdf_file_name'],
                    base64_encode($pdfData)));
            app('sentry')->captureMessage('Письмо добавлено в очередь: ' . print_r($input['email']['to'], true), [],
                ['level' => 'info']);
        } else {
            if ($pdfFileName && @$input['open_file_in_browser']) {

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
