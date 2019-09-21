<?php
namespace app\common\model;
use think\Model;
/**
* 电子发票
*/
class ElecronickInvoice extends Model
{
	/*
	商户批量入驻申请接口
	*/
	public function merchantlist_apply()
	{
		import('Alipay.AopSdk');
		$aop = new \AopClient ();
		$aop->gatewayUrl = 'https://openapi.alipaydev.com/gateway.do';//沙箱提交地址
		$aop->appId = '2016091800539404';
		$aop->rsaPrivateKey = 'MIIEowIBAAKCAQEAn5aCzWGuXB2Ydm2kNEqigp2cYSSMCUzUBURHX54ZbdpY3Lps0WeqYvok+HjeOEoiztLlKagzcZfUmIxeF4n7PRsWRX4sUZca4dCLmECpCVHxkCcdto6a6qajo4o+tqraraXbULkcDsHKi9icamKtTBghu9t1pqcFH3dkaAa5PVxD8/CnYiFRX8LB+iaa8ZeGyT0QC/cVmMYaBZnwatWf1oGF8Z3lXNMi3N9v+hA57Xa3X63+6VyYxFyq1f8ob0bpepPuc/+io+2NX2OEMkW2FlzMh2mPBu9uKyLrm+JWH4mdtDZFP1sSI1DdzQlUcpaJREE8kqzi3gKidAT4OyQvmQIDAQABAoIBAFwJ7RRQYsPjmburjklORh14kuj/r/fpJFqJP3So9NeDVz6uEfZPiFgfFlVrOBZUw3Bu9xWoWUsJGuaEBfwjaw+Z4KVhmGPR9wIHSYsct6CVbvEJbjyMUFJGmxfFsledgBMoFa2fpMvp/kvbOJKuqG27eTEj592ec/jq3bCVOB5nf+e73zdEmcl5HlGrbrP6ZglZcE2g6n+uDU0p9QVCx6Fgkf50CCmsA7jGxT2be2rCDpXItCh5+O222sFIMIwEpfYcECuvrHfQ3hiZ5gV6SMSeZ3LQSQAx5KwUk3HGnr3/2IT+z4DZgTU+0eNE4mGwykIKBAQJNBoH93OsO5UvuFECgYEAzYhR3pWPQhUuulMqFbeukU4X2n8jjcOqY8jzGwLi1SeAD72I3b2ZkI9F3KLKZl6xDeDRY0nED5TNOzrIuAqGveJwqJoje92Z2mRPC4WH/bc7pgNvA3PlWaK7El91g0AMV0TvtZtRmLvWSPqScTY8K/fnd0VDuNKq8RRQuD34U4UCgYEAxsYi7uV7pAGQ0Gm2aQp22oomseASccFQUWz+7B1RW4DcF6pYgxNtr++/4jeMvx8yZpKHVf7ZzEnLLzUcFF+0FKDsF1Qs0S/9md64qmSbN30GQta/Mcf4pbg1Pmb+PK8LcbVmusYQi31o4ez2RuyLl1SnDPC4cQmdbnH8k81itgUCgYBRy+jZLqhe4lNAcAyVrA5bYbr4iBS8PJy5LaYin9lqf1fl68ZmiShucbaaAmeOqizImyp520ed89hhtBlhtT6+nzm6v+1TRHQQiE81BKWEgcHJZiBuPVePfsX4n+kCnSDMMnE701577HVxgdd6Gt2DmhuSSgyTp7PEUhN136POiQKBgFRiXQYqKfkQKRgxKJ0jMh3ItHCi/XGJbb2Dlh1KvPUpmUX6rSTIJLKiB0XT605fwhfFcOrTDEcrtLRPyaHklyCCyHeG5pfP1ctyv9amazZ9PyE05WoOyMIhl4jsVFYSwbr+FaSI3RE6pkHzRQIK/Z+68kgOEV77g/gVL/LXKGa5AoGBAMq/ojc1S515syq4FMM7miSxcwn9O0H0l9bAK9zF2dHQRFvyNtCbczO60ymEFV6BjIA/SzwRU+FWfcyzi9qRlUdNvXpcvUIjZXifd059iagabAHC0Dtf5DuPEmpF9M2fbAnMOo+Pp8iP3RHHZ5BVwfunBJjV2uCsmRk3Yx+n+dIr';
		$aop->alipayrsaPublicKey='MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDIgHnOn7LLILlKETd6BFRJ0GqgS2Y3mn1wMQmyh9zEyWlz5p1zrahRahbXAfCfSqshSNfqOmAQzSHRVjCqjsAw1jyqrXaPdKBmr90DIpIxmIyKXv4GGAkPyJ/6FTFY99uhpiq0qadD/uSzQsefWo0aTvP/65zi3eof7TcZ32oWpwIDAQAB';
		$aop->apiVersion = '1.0';
		$aop->signType = 'RSA2';
		$aop->postCharset='GBK';
		$aop->format='json';
		$request = new \AlipayEbppInvoiceMerchantlistEnterApplyRequest();
		$request->setBizContent("{" .
		"\"merchant_base\":{" .
		"\"m_short_name\":\"MYTC\"," .
		"\"m_name\":\"古力商家\"" .
		"    }," .
		"      \"sub_merchant_list\":[{" .
		"        \"sub_m_short_name\":\"MYTC_HZ\"," .
		"\"sub_m_name\":\"齐协网络科技停车场\"," .
		"\"register_no\":\"91500000747150346A\"," .
		"\"pid\":\"2088102176173127\"" .
		"        }]," .
		"\"sub_merchant_common_info\":{" .
		"\"product_code\":\"PAYMENT_OPEN\"," .
		"\"s_short_name\":\"SAD\"" .
		"    }" .
		"  }");
		$result = $aop->execute ( $request); 

		$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
		$resultCode = $result->$responseNode->code;
		if(!empty($resultCode)&&$resultCode == 10000){
		echo "成功";
		} else {
		echo "失败";
		}
	}
}
