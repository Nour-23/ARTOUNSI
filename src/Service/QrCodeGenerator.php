<?php
 
 namespace App\Service;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;

use Endroid\QrCode\Label\Font\NotoSans;
use Endroid\QrCode\Writer\Result\ResultInterface;
use Endroid\QrCode\Writer\SvgWriter;
use App\Entity\User;


class QrCodeGenerator 
{
 
public function createQrCode( User $user): ResultInterface
{
    $id = $user->getId();
    $name = $user->getName();
    $family = $user->getFamilyname();
    $email = $user->getEmail();
   $tel= $user->getNumtel();
   $adr= $user->getAdresse();
    $info = "
    $id
    $name
    $family
    $email
    $tel
    $adr
    
    ";


 $result = Builder::create()
 ->writer(new SvgWriter())
 ->writerOptions([])
 ->data($info)
 ->encoding(new Encoding('UTF-8'))
 ->size(200)
 ->margin(10)
 ->labelText('Vous trouvez vos informations ici')
 ->labelFont(new NotoSans(20))
 ->validateResult(false)
 ->build();

return $result;
}}