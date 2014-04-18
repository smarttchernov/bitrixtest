<?php
namespace Bitrix\Main\DB;

class ArrayResult extends Result
{
	public function __construct($result)
	{
		parent::__construct($result);
	}

	protected function convertDataFromDb($value, $fieldType)
	{
		throw new \Bitrix\Main\NotImplementedException("convertDataFromDb is not implemented for arrays");
	}

	public function getSelectedRowsCount()
	{
		return count($this->resource);
	}

	public function getFieldsCount()
	{
		foreach($this->resource as $row)
		{
			return count(array_keys($row));
		}
		return 0;
	}

	public function getFieldName($column)
	{
		foreach($this->resource as $row)
		{
			$keys = array_keys($row);
			return $keys[$column];
		}
		return null;
	}

	public function getResultFields()
	{
		return null;
	}

	protected function fetchRowInternal()
	{
		$val = current($this->resource);
		next($this->resource);
		return $val;
	}
}
