<?php


namespace ShardMatrix\DB;




class TestRow extends ResultRow {
	public function getTest(){
		return $this->getUuid()->getNode()->getName();
	}
}