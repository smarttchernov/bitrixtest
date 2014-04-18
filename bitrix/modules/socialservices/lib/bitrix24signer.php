<?
namespace Bitrix\Socialservices;

use Bitrix\Main\Web\Json;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Main\Security\Sign\HmacAlgorithm;

class Bitrix24Signer
	extends Signer
{
	public function __construct()
	{
		parent::__construct(new HmacAlgorithm('sha256'));
	}

	public function sign($value, $salt = null)
	{
		$valueEnc = base64_encode(Json::encode($value));
		return parent::sign($valueEnc, $salt);
	}

	public function unsign($signedValue, $salt = null)
	{
		$encodedValue = parent::unsign($signedValue, $salt);
		return Json::decode(base64_decode($encodedValue));
	}
}