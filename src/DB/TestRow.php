<?php


namespace ShardMatrix\DB;




class TestRow extends DataRow {
	public function getTest(){
		return $this->getUuid()->getNode()->getName();
	}
}