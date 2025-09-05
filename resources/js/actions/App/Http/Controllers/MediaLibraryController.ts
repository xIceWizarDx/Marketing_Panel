import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
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
* @see \App\Http\Controllers\MediaLibraryController::bulkDelete
 * @see app/Http/Controllers/MediaLibraryController.php:170
 * @route '/media/bulk-delete'
 */
export const bulkDelete = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: bulkDelete.url(options),
    method: 'post',
})

bulkDelete.definition = {
    methods: ["post"],
    url: '/media/bulk-delete',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MediaLibraryController::bulkDelete
 * @see app/Http/Controllers/MediaLibraryController.php:170
 * @route '/media/bulk-delete'
 */
bulkDelete.url = (options?: RouteQueryOptions) => {
    return bulkDelete.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MediaLibraryController::bulkDelete
 * @see app/Http/Controllers/MediaLibraryController.php:170
 * @route '/media/bulk-delete'
 */
bulkDelete.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: bulkDelete.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\MediaLibraryController::bulkDelete
 * @see app/Http/Controllers/MediaLibraryController.php:170
 * @route '/media/bulk-delete'
 */
    const bulkDeleteForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: bulkDelete.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\MediaLibraryController::bulkDelete
 * @see app/Http/Controllers/MediaLibraryController.php:170
 * @route '/media/bulk-delete'
 */
        bulkDeleteForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: bulkDelete.url(options),
            method: 'post',
        })
    
    bulkDelete.form = bulkDeleteForm
/**
* @see \App\Http\Controllers\MediaLibraryController::bulkTag
 * @see app/Http/Controllers/MediaLibraryController.php:180
 * @route '/media/bulk-tag'
 */
export const bulkTag = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: bulkTag.url(options),
    method: 'post',
})

bulkTag.definition = {
    methods: ["post"],
    url: '/media/bulk-tag',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MediaLibraryController::bulkTag
 * @see app/Http/Controllers/MediaLibraryController.php:180
 * @route '/media/bulk-tag'
 */
bulkTag.url = (options?: RouteQueryOptions) => {
    return bulkTag.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MediaLibraryController::bulkTag
 * @see app/Http/Controllers/MediaLibraryController.php:180
 * @route '/media/bulk-tag'
 */
bulkTag.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: bulkTag.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\MediaLibraryController::bulkTag
 * @see app/Http/Controllers/MediaLibraryController.php:180
 * @route '/media/bulk-tag'
 */
    const bulkTagForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: bulkTag.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\MediaLibraryController::bulkTag
 * @see app/Http/Controllers/MediaLibraryController.php:180
 * @route '/media/bulk-tag'
 */
        bulkTagForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: bulkTag.url(options),
            method: 'post',
        })
    
    bulkTag.form = bulkTagForm
const MediaLibraryController = { index, upload, list, bulkDelete, bulkTag }

export default MediaLibraryController