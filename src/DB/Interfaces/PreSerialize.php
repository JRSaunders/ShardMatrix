<?php


namespace ShardMatrix\DB\Interfaces;


interface PreSerialize {
	public function __preSerialize():void;
}