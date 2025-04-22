# Eloquent Crud

This package is used to generate cruds based on eloquent query.

| Package Version | Bootstrap | Methods | Views engine | Dependencies |
| --------------- | --------- | ------- | ------------ | ------------ |
| 1.0             | 5         | en      | vue          | npm          |

-   Only index for now

```
class TestController extends EloquentCrudController
{
    public function setup(Request $request)
    {
        $query = Authusuario::query()
            ->select(
                'usuarioid', 'nombre', 'email', 'telefono', 'direccion', 'activo',
                'fraude', 'cliente_seguro', 'es_empleado'
            );
        $this->setQuery($query);
        $this->setExtraButton([
            'title'  => 'Ordenes',
            'url'    => '/ordenes/ordenes?u={id}&listar=on',
            'icon'   => 'fa fa-shopping-cart',
            'target' => '_blank',
        ]);
        $this->setPermissions(Cancerbero::tienePermisosCrud('admin.usuarios'));
    }
}
```
