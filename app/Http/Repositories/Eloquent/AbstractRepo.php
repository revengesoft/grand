<?php

namespace App\Http\Repositories\Eloquent;

use App\Http\Repositories\Interfaces\AbstractRepoInterface;
use Illuminate\Pagination\Paginator;


class AbstractRepo implements AbstractRepoInterface
{
    protected $model;

    public function __construct(string $model)
    {
        $this->model = $model;
    }

    public function findOrFail($id)
    {
        return $this->model::findOrfail($id);
    }

    public function getAll()
    {
        return $this->model::orderBy('id','desc')->get();
    }

    public function getAllOrderBy($column,$sort)
    {
        return $this->model::orderBy($column,$sort)->get();
    }

    public function findWhere($column, $value)
    {
        return $this->model::where($column, $value)->get();
    }

    public function findWhereIn($column,Array $values)
    {
        return $this->model::whereIn($column, $values)->get();
    }

    public function getWhereOperand($column, $operand, $value)
    {
        return $this->model::where($column,$operand, $value)->get();
    }


    public function findWhereFirst($column, $value)
    {
        return $this->model::where($column, $value)->firstOrFail();
    }

    public function Paginate(array $input, array $wheres, $model = null)
    {
        $currentPage = $input["offset"];
        Paginator::currentPageResolver(function () use ($currentPage) {
            return $currentPage;
        });
        $this->model = new $this->model;
        if ($input["deleted"] != "false") {
            $this->model = $this->model->onlyTrashed();
        }
        if (count($wheres)) {
            foreach ($wheres as $where) {
                switch ($where[1]) {
                    case 'in':
                        $this->model = $this->model->whereIn($where[0], $where[2]);
                        break;
                    default:
                        $this->model = $this->model->where($where[0], $where[1], $where[2]);
                }
            }
            $this->model = $this->model->orderBy($input["field"], $input["sort"]);
            return $input["paginate"] != "false" ? $this->model->paginate($input["limit"]) : $this->model->get();
        }
        $this->model = $this->model->orderBy($input["field"], $input["sort"]);
        return $input["paginate"] != "false"? $this->model->paginate($input["limit"]) : $this->model->get();
    }

    public function bulkDelete(array $ids)
    {
        $allRows = $this->model::withTrashed()->find($ids);
        foreach ($allRows as $row) {

            if ($row->trashed()) {
                $row->forceDelete();
            } else {
                $row->delete();
            }
        }
        return true;
    }

    public function bulkRestore(array $ids)
    {
        $allRows = $this->model::onlyTrashed()->find($ids);
        foreach ($allRows as $row) {
            $row->restore();
        }
        return true;
    }

    public function inputs(Array $request)
    {
        return [
            'limit' => $request['limit'] ?? 20,
            'offset' => $request['offset'] ?? 0,
            'sort' => $request['sort'] ?? 'ASC',
            'resource' => $request['resource'] ?? 'all',
            'field' => $request['field'] ?? 'id',
            'deleted' => $request['deleted'] ?? "false",
            'paginate' => $request['paginate'] ?? "true",
            'columns' => $request['columns'] ?? [],
            'operand' => $request['operand'] ?? [],
            'column_values' => $request['column_values'] ?? [],
        ];
    }

    public function whereOptions($input, $columns)
    {
        $wheres = [];
        $x = 0;
        foreach ($input["columns"] as $row) {
            if (in_array($row, array_values($columns))) {
                if (strtolower($input["operand"][$x]) == "like") {
                    $wheres[] = [$row, strtolower($input["operand"][$x]), '%' . $input["column_values"][$x] . '%'];
                } else {
                    $wheres[] = [$row, strtolower($input["operand"][$x]), $input["column_values"][$x]];
                }
                $x++;
            }
        }
        return $wheres;
    }

    public function create($data)
    {
        return $this->model::create($data);
    }

    public function update($data, $item)
    {
        return $item->update($data);
    }

}