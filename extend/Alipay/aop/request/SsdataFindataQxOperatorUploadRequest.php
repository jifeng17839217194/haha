<?php
/**
 * ALIPAY API: ssdata.findata.qx.operator.upload request
 *
 * @author auto create
 * @since 1.0, 2017-10-30 20:08:20
 */
class SsdataFindataQxOperatorUploadRequest
{
	/** 
	 * 上传的文件数据类型,用来描述file_content
	 **/
	private $dataType;
	
	/** 
	 * 扩展信息
	 **/
	private $extParam;
	
	/** 
	 * 上传文件
	 **/
	private $fileContent;
	
	/** 
	 * C端用户身份证
	 **/
	private $idCardNo;
	
	/** 
	 * C端用户手机号
	 **/
	private $phone;
	
	/** 
	 * C端用户姓名
	 **/
	private $userName;

	private $apiParas = array();
	private $terminalType;
	private $terminalInfo;
	private $prodCode;
	private $apiVersion="1.0";
	private $notifyUrl;
	private $returnUrl;
    private $needEncrypt=false;

	
	public function setDataType($dataType)
	{
		$this->dataType = $dataType;
		$this->apiParas["data_type"] = $dataType;
	}

	public function getDataType()
	{
		return $this->dataType;
	}

	public function setExtParam($extParam)
	{
		$this->extParam = $extParam;
		$this->apiParas["ext_param"] = $extParam;
	}

	public function getExtParam()
	{
		return $this->extParam;
	}

	public function setFileContent($fileContent)
	{
		$this->fileContent = $fileContent;
		$this->apiParas["file_content"] = $fileContent;
	}

	public function getFileContent()
	{
		return $this->fileContent;
	}

	public function setIdCardNo($idCardNo)
	{
		$this->idCardNo = $idCardNo;
		$this->apiParas["id_card_no"] = $idCardNo;
	}

	public function getIdCardNo()
	{
		return $this->idCardNo;
	}

	public function setPhone($phone)
	{
		$this->phone = $phone;
		$this->apiParas["phone"] = $phone;
	}

	public function getPhone()
	{
		return $this->phone;
	}

	public function setUserName($userName)
	{
		$this->userName = $userName;
		$this->apiParas["user_name"] = $userName;
	}

	public function getUserName()
	{
		return $this->userName;
	}

	public function getApiMethodName()
	{
		return "ssdata.findata.qx.operator.upload";
	}

	public function setNotifyUrl($notifyUrl)
	{
		$this->notifyUrl=$notifyUrl;
	}

	public function getNotifyUrl()
	{
		return $this->notifyUrl;
	}

	public function setReturnUrl($returnUrl)
	{
		$this->returnUrl=$returnUrl;
	}

	public function getReturnUrl()
	{
		return $this->returnUrl;
	}

	public function getApiParas()
	{
		return $this->apiParas;
	}

	public function getTerminalType()
	{
		return $this->terminalType;
	}

	public function setTerminalType($terminalType)
	{
		$this->terminalType = $terminalType;
	}

	public function getTerminalInfo()
	{
		return $this->terminalInfo;
	}

	public function setTerminalInfo($terminalInfo)
	{
		$this->terminalInfo = $terminalInfo;
	}

	public function getProdCode()
	{
		return $this->prodCode;
	}

	public function setProdCode($prodCode)
	{
		$this->prodCode = $prodCode;
	}

	public function setApiVersion($apiVersion)
	{
		$this->apiVersion=$apiVersion;
	}

	public function getApiVersion()
	{
		return $this->apiVersion;
	}

  public function setNeedEncrypt($needEncrypt)
  {

     $this->needEncrypt=$needEncrypt;

  }

  public function getNeedEncrypt()
  {
    return $this->needEncrypt;
  }

}
