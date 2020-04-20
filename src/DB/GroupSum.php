<?php


namespace ShardMatrix\DB;


class GroupSum extends ResultRow {

	public function getSum(): int {
		return $this->row->sum ?? 0;
	}

	public function getColumn(): ?string {
		return $this->row->column ?? null;
	}
}