<?php
namespace CrudApiRestfull\Traits;

use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Trait para crear la data da la paginación
 */
trait PaginationTrait
{

    public function makeMetaData($data): array
    {
        if (!isset($data)) {
            return null;
        }
        return [
            'total'=>$data->total(),
            'count'=>$data->count(),
            'pagination'=>$data->perPage(),
            'page'=>$data->currentPage(),
            'lastPage'=>$data->lastPage(),
            'hasMorePages'=>$data->hasMorePages(),
            'nextPageUrl'=>$data->nextPageUrl(),
            'previousPageUrl'=>$data->previousPageUrl(),
            '_links' => $data->getUrlRange(1, $data->lastPage())
        ];
    }

    public function makeMetaDataApiResource($resourceCollection): LengthAwarePaginator
    {
        //TODO se convierte el api collection en un Paginator de Eloquent para que la metadata este acorde a lo que requiere el front

        return new LengthAwarePaginator(
            $resourceCollection->collection,
            $resourceCollection->total(),
            $resourceCollection->perPage(),
            $resourceCollection->currentPage()
        );
    }
}
