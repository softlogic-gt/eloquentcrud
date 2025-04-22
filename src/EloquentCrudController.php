<?php
namespace App\Http\Controllers\Crud2;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Routing\Controller as BaseController;

class EloquentCrudController extends BaseController
{
    private ?Builder $query = null;
    private ?Model $model   = null;
    private $primaryKey     = 'id';
    private $headers        = [];
    private $casts          = [];
    private $hiddens        = [];
    private $take           = 50;
    private $extraButtons   = [];
    private $permissions    = ['create' => false, 'update' => false, 'destroy' => false];

    public function setup(Request $request)
    {
        //Must be overridden in parent
        abort(400, "This method must be overriden in parent");
    }

    private function privateSetup(Request $request)
    {
        // if (!$this->query) {
        //     abort(400, "setQuery is required");
        // }

        $this->setup($request);
        $this->model      = $this->query->getModel();
        $this->primaryKey = $this->model->getKeyName();
        $this->casts      = $this->model->getCasts();

        $headers       = $this->query->getQuery()->columns;
        $this->headers = collect($headers)->map(function ($header) {
            return [
                'column' => $header,
                'header' => str_replace('_', ' ', ucfirst($header)),
                'type'   => $this->casts[$header] ?? 'string',
            ];
        });
    }

    public function index(Request $request)
    {
        $this->privateSetup($request);
        $props = [
            'extrabuttons' => $this->extraButtons,
            'headers'      => $this->headers,
            'casts'        => $this->casts,
            'permissions'  => $this->permissions,
            'baseurl'      => url()->current(),
        ];

        return view('component')
            ->with('component', 'crud2_index')
            ->with('props', $props);
    }

    public function edit(Request $request, $id)
    {
        $this->privateSetup($request);

        $headers = collect($this->headers)->filter(function ($header) {
            return $header['column'] != $this->primaryKey;
        })->values();

        $props = [
            'headers' => $headers,
            'id'      => $id,
        ];

        return view('component')
            ->with('component', 'crud2_edit')
            ->with('props', $props);
    }

    public function data(Request $request)
    {
        $filtered = false;
        $this->privateSetup($request);

        $result = $this->query
            ->addSelect($this->primaryKey . ' AS __id__');

        foreach ($request->filters as $filter) {
            if ($filter['column'] && $filter['operator'] && $filter['value']) {
                $filtered = true;
                $value    = $filter['value'];
                if ($filter['operator'] == 'LIKE') {
                    $value = '%' . $value . '%';
                }

                $result->where($filter['column']['column'], $filter['operator'], $value);
            }
        }

        if ($request->sort && $request->sort['column']) {
            $result->orderBy($request->sort['column'], $request->sort['direction']);
        }

        $result = $result->paginate($this->take)->withQueryString();

        //Remove all appends.  May be interesting to validate with the selected fields
        //to be able to use them here if needed
        $result->getCollection()->transform(function ($item) {
            return $item->makeHidden($item->getAppends());
        });

        return response()->json([
            'result'   => $result,
            'filtered' => $filtered,
            'links'    => (string) $result->onEachSide(3)->links(),
        ]);
    }

    public function detail(Request $request, $id)
    {
        $this->privateSetup($request);

        $fields = $this->query
            ->findOrFail($id);

        $fields = $fields->makeHidden([$this->primaryKey]);

        return response()->json([
            'id'     => $id,
            'fields' => $fields,
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->privateSetup($request);

        $updated = $this->model->findOrFail($id);
        $updated->fill($request->all());
        $updated->fill($this->hiddens);
        $updated->save();

        return response()->json('ok');
    }

    public function destroy(Request $request, $id)
    {
        $this->privateSetup($request);

        $this->model->where($this->primaryKey, $id)->delete();

        return response()->json([
            'id' => $id,
        ]);
    }

    public function setQuery(Builder $aQuery)
    {
        $this->query = $aQuery;
    }

    public function setHiddens(array $aHiddens)
    {
        $this->hiddens = $aHiddens;
    }

    public function setExtraButton($aParams)
    {
        $allowed = ['url', 'title', 'target', 'icon', 'class', 'confirm', 'confirmmessage'];

        foreach ($aParams as $key => $val) {
            //We validate that all variables are permitted
            if (!in_array($key, $allowed)) {
                dd('setExtraButton doesn\t recognize parameter: ' . $key . '! parameters allowed: ' . implode(', ', $allowed));
            }
        }
        if (!array_key_exists('url', $aParams)) {
            dd('setExtraButton must have a value for "url"');
        }

        $icon           = (!array_key_exists('icon', $aParams) ? 'fa fa-star' : $aParams['icon']);
        $class          = (!array_key_exists('class', $aParams) ? 'default' : $aParams['class']);
        $title          = (!array_key_exists('title', $aParams) ? '' : $aParams['title']);
        $target         = (!array_key_exists('target', $aParams) ? '' : $aParams['target']);
        $confirm        = (!array_key_exists('confirm', $aParams) ? false : $aParams['confirm']);
        $confirmmessage = (!array_key_exists('confirmmessage', $aParams) ? '¿Estas seguro?' : $aParams['confirmmessage']);

        $arr = [
            'url'            => $aParams['url'],
            'title'          => $title,
            'icon'           => $icon,
            'class'          => $class,
            'target'         => $target,
            'confirm'        => $confirm,
            'confirmmessage' => $confirmmessage,
        ];

        $this->extraButtons[] = $arr;
    }

    public function setPermissions($permissions)
    {
        $this->permissions = $permissions;
    }

}
