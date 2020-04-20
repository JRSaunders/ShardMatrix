<?php


namespace ShardMatrix\DB;


class Where {
	const LESS_THAN = '<';
	const MORE_THAN = '>';
	const EQUALS = '=';
	const NOT_EQUALS = '!=';

	protected string $column;
	protected string $operator;
	protected string $value;

	public function __construct( string $column, string $value, string $operator = self::EQUALS ) {
		$this->setColumn( $column )->setValue( $value )->setOperator( $operator );
	}

	/**
	 * @return string
	 */
	public function getColumn(): string {
		return $this->column;
	}

	/**
	 * @param string $column
	 */
	public function setColumn( string $column ): Where {
		$this->column = $column;

		return $this;
	}

	/**
	 * @param string $operator
	 *
	 * @return Where
	 */
	public function setOperator( string $operator ): Where {
		$this->operator = $operator;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getOperator(): string {
		return $this->operator;
	}

	/**
	 * @param string $value
	 *
	 * @return Where
	 */
	public function setValue( string $value ): Where {
		$this->value = $value;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getValue(): string {
		return $this->value;
	}


}