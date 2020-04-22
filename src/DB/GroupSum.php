<?php


namespace ShardMatrix\DB;


class GroupSum extends DataRow {

	public function getSum(): int {
		return $this->row->sum ?? 0;
	}

	public function getColumn(): ?string {
		return $this->row->column ?? null;
	}
}