import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\MediaLibraryController::index
 * @see app/Http/Controllers/MediaLibraryController.php:14
 * @route '/media'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/media',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MediaLibraryController::index
 * @see app/Http/Controllers/MediaLibraryController.php:14
 * @route '/media'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MediaLibraryController::index
 * @see app/Http/Controllers/MediaLibraryController.php:14
 * @route '/media'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\MediaLibraryController::index
 * @see app/Http/Controllers/MediaLibraryController.php:14
 * @route '/media'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\MediaLibraryController::index
 * @see app/Http/Controllers/MediaLibraryController.php:14
 * @route '/media'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\MediaLibraryController::index
 * @see app/Http/Controllers/MediaLibraryController.php:14
 * @route '/media'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\MediaLibraryController::index
 * @see app/Http/Controllers/MediaLibraryController.php:14
 * @route '/media'
 */
        indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    index.form = indexForm
/**
* @see \App\Http\Controllers\MediaLibraryController::upload
 * @see app/Http/Controllers/MediaLibraryController.php:102
 * @route '/media/upload'
 */
export const upload = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: upload.url(options),
    method: 'post',
})

upload.definition = {
    methods: ["post"],
    url: '/media/upload',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MediaLibraryController::upload
 * @see app/Http/Controllers/MediaLibraryController.php:102
 * @route '/media/upload'
 */
upload.url = (options?: RouteQueryOptions) => {
    return upload.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MediaLibraryController::upload
 * @see app/Http/Controllers/MediaLibraryController.php:102
 * @route '/media/upload'
 */
upload.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: upload.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\MediaLibraryController::upload
 * @see app/Http/Controllers/MediaLibraryController.php:102
 * @route '/media/upload'
 */
    const uploadForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: upload.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\MediaLibraryController::upload
 * @see app/Http/Controllers/MediaLibraryController.php:102
 * @route '/media/upload'
 */
        uploadForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: upload.url(options),
            method: 'post',
        })
    
    upload.form = uploadForm
/**
* @see \App\Http\Controllers\MediaLibraryController::list
 * @see app/Http/Controllers/MediaLibraryController.php:144
 * @route '/media/list'
 */
export const list = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: list.url(options),
    method: 'get',
})

list.definition = {
    methods: ["get","head"],
    url: '/media/list',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MediaLibraryController::list
 * @see app/Http/Controllers/MediaLibraryController.php:144
 * @route '/media/list'
 */
list.url = (options?: RouteQueryOptions) => {
    return list.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MediaLibraryController::list
 * @see app/Http/Controllers/MediaLibraryController.php:144
 * @route '/media/list'
 */
list.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: list.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\MediaLibraryController::list
 * @see app/Http/Controllers/MediaLibraryController.php:144
 * @route '/media/list'
 */
list.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: list.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\MediaLibraryController::list
 * @see app/Http/Controllers/MediaLibraryController.php:144
 * @route '/media/list'
 */
    const listForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: list.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\MediaLibraryController::list
 * @see app/Http/Controllers/MediaLibraryController.php:144
 * @route '/media/list'
 */
        listForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: list.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\MediaLibraryController::list
 * @see app/Http/Controllers/MediaLibraryController.php:144
 * @route '/media/list'
 */
        listForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: list.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    list.form = listForm
/**
* @see \App\Http\Controllers\MediaLibraryController::bulk_delete
 * @see app/Http/Controllers/MediaLibraryController.php:170
 * @route '/media/bulk-delete'
 */
export const bulk_delete = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: bulk_delete.url(options),
    method: 'post',
})

bulk_delete.definition = {
    methods: ["post"],
    url: '/media/bulk-delete',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MediaLibraryController::bulk_delete
 * @see app/Http/Controllers/MediaLibraryController.php:170
 * @route '/media/bulk-delete'
 */
bulk_delete.url = (options?: RouteQueryOptions) => {
    return bulk_delete.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MediaLibraryController::bulk_delete
 * @see app/Http/Controllers/MediaLibraryController.php:170
 * @route '/media/bulk-delete'
 */
bulk_delete.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: bulk_delete.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\MediaLibraryController::bulk_delete
 * @see app/Http/Controllers/MediaLibraryController.php:170
 * @route '/media/bulk-delete'
 */
    const bulk_deleteForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: bulk_delete.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\MediaLibraryController::bulk_delete
 * @see app/Http/Controllers/MediaLibraryController.php:170
 * @route '/media/bulk-delete'
 */
        bulk_deleteForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: bulk_delete.url(options),
            method: 'post',
        })
    
    bulk_delete.form = bulk_deleteForm
/**
* @see \App\Http\Controllers\MediaLibraryController::bulk_tag
 * @see app/Http/Controllers/MediaLibraryController.php:180
 * @route '/media/bulk-tag'
 */
export const bulk_tag = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: bulk_tag.url(options),
    method: 'post',
})

bulk_tag.definition = {
    methods: ["post"],
    url: '/media/bulk-tag',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MediaLibraryController::bulk_tag
 * @see app/Http/Controllers/MediaLibraryController.php:180
 * @route '/media/bulk-tag'
 */
bulk_tag.url = (options?: RouteQueryOptions) => {
    return bulk_tag.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MediaLibraryController::bulk_tag
 * @see app/Http/Controllers/MediaLibraryController.php:180
 * @route '/media/bulk-tag'
 */
bulk_tag.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: bulk_tag.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\MediaLibraryController::bulk_tag
 * @see app/Http/Controllers/MediaLibraryController.php:180
 * @route '/media/bulk-tag'
 */
    const bulk_tagForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: bulk_tag.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\MediaLibraryController::bulk_tag
 * @see app/Http/Controllers/MediaLibraryController.php:180
 * @route '/media/bulk-tag'
 */
        bulk_tagForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: bulk_tag.url(options),
            method: 'post',
        })
    
    bulk_tag.form = bulk_tagForm
const media = {
    index,
upload,
list,
bulk_delete,
bulk_tag,
}

export default media