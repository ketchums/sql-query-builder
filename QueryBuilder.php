<?php 

declare(strict_types = 1);

class QueryBuilder 
{
	private $tableName;
	private $whereClauses;
	private $selectColumns;
	private $maxResults;
	private $orderBy;

	public function __construct(string $tableName) {
		$this->tableName = $tableName;
		$this->whereClauses = [];
		$this->selectColumns = ['*'];
		$this->maxResults = -1;
	}

	public function where(string $column, string $valueOrOperator, string $value = null) : QueryBuilder {
		if ($value == null) {
			return $this->appendWhere($column, '=', $valueOrOperator);
		}

		return $this->appendWhere($column, $valueOrOperator, $value);
	}

	private function appendWhere(string $column, string $operator, string $value, string $delimiter = 'AND') : QueryBuilder {
		$this->whereClauses[] =  [
			'column' => $column,
			'operator' => $operator,
			'value' => $value,
			'delimiter' => $delimiter
		];

		return $this;
	}

	public function whereRaw(string $rawSql) : QueryBuilder {
		$this->whereClauses[] = [
			'raw_sql' => $rawSql,
			'delimiter' => 'AND'
		];

		return $this;
	}

	public function orWhere(string $column, string $valueOrOperator, string $value = null) : QueryBuilder {
		if ($value == null) {
			return $this->appendWhere($column, '=', $valueOrOperator, 'OR');
		}

		return $this->appendWhere($column, $valueOrOperator, $value);
	}

	public function orWhereRaw(string $rawSql) : QueryBuilder {
		$this->whereClauses[] = [
			'raw_sql' => $rawSql,
			'delimiter' => 'OR'
		];

		return $this;
	}

	public function select(array $columns) : QueryBuilder {
		$this->selectColumns = [];

		foreach ($columns as $column) {
			$this->selectColumns[] = "`{$column}`";
		}

		return $this;
	}

	public function limit(int $amount) : QueryBuilder {
		$this->maxResults = $amount;
		return $this;
	}

	public function orderBy(string $column, string $direction) : QueryBuilder {
		$this->orderBy = $column . ' ' . $direction;
		return $this;
	}

	public function buildDefaultQuery() : string {
		return 'SELECT ' . implode(', ', $this->selectColumns) . ' FROM ' . $this->tableName . ' ';
	}

	public function parseClauseValue(string $value) : string {
		return is_numeric($value) ? $value : '\'' . $value . '\'';
	}

	public function appendWhereClausesToQuery(string $query) : string {
		$query .= 'WHERE ';

		foreach ($this->whereClauses as $key => $clause) {
			if ($key >= 1) {
				$query .= ' ' . $clause['delimiter'] . ' ';
			}

			if (array_key_exists('raw_sql', $clause)) {
				$query .= $clause['raw_sql'];
			}
			else {
				$parsedValue = $this->parseClauseValue($clause['value']);
				$query .= $clause['column'] . ' ' . $clause['operator'] . ' ' . $parsedValue;
			}
		}

		return $query;
	}

	public function appendOrderByToQuery(string $query) : string {
		$query .= ' ORDER BY ' . $this->orderBy;
		return $query;
	}

	public function appendLimitSqlToQuery(string $query) : string {
		$query .= ' LIMIT ' . $this->maxResults . ';';
		return $query;
	}

	public function toSql() : string {
		$query = $this->buildDefaultQuery();

		if (count($this->whereClauses) > 0) {
			$query = $this->appendWhereClausesToQuery($query);
		}

		if (strlen($this->orderBy) > 0) {
			$query = $this->appendOrderByToQuery($query);
		}

		if ($this->maxResults >= 0) {
			$query = $this->appendLimitSqlToQuery($query);
		}

		return $query;
	}
}