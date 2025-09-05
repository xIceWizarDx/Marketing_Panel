import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\SocialPostController::index
 * @see app/Http/Controllers/SocialPostController.php:15
 * @route '/posts'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/posts',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SocialPostController::index
 * @see app/Http/Controllers/SocialPostController.php:15
 * @route '/posts'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SocialPostController::index
 * @see app/Http/Controllers/SocialPostController.php:15
 * @route '/posts'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SocialPostController::index
 * @see app/Http/Controllers/SocialPostController.php:15
 * @route '/posts'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\SocialPostController::index
 * @see app/Http/Controllers/SocialPostController.php:15
 * @route '/posts'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\SocialPostController::index
 * @see app/Http/Controllers/SocialPostController.php:15
 * @route '/posts'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\SocialPostController::index
 * @see app/Http/Controllers/SocialPostController.php:15
 * @route '/posts'
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
* @see \App\Http\Controllers\SocialPostController::create
 * @see app/Http/Controllers/SocialPostController.php:60
 * @route '/posts/create'
 */
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/posts/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SocialPostController::create
 * @see app/Http/Controllers/SocialPostController.php:60
 * @route '/posts/create'
 */
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SocialPostController::create
 * @see app/Http/Controllers/SocialPostController.php:60
 * @route '/posts/create'
 */
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SocialPostController::create
 * @see app/Http/Controllers/SocialPostController.php:60
 * @route '/posts/create'
 */
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\SocialPostController::create
 * @see app/Http/Controllers/SocialPostController.php:60
 * @route '/posts/create'
 */
    const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: create.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\SocialPostController::create
 * @see app/Http/Controllers/SocialPostController.php:60
 * @route '/posts/create'
 */
        createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: create.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\SocialPostController::create
 * @see app/Http/Controllers/SocialPostController.php:60
 * @route '/posts/create'
 */
        createForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: create.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    create.form = createForm
/**
* @see \App\Http\Controllers\SocialPostController::storeDraft
 * @see app/Http/Controllers/SocialPostController.php:85
 * @route '/posts/drafts'
 */
export const storeDraft = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeDraft.url(options),
    method: 'post',
})

storeDraft.definition = {
    methods: ["post"],
    url: '/posts/drafts',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SocialPostController::storeDraft
 * @see app/Http/Controllers/SocialPostController.php:85
 * @route '/posts/drafts'
 */
storeDraft.url = (options?: RouteQueryOptions) => {
    return storeDraft.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SocialPostController::storeDraft
 * @see app/Http/Controllers/SocialPostController.php:85
 * @route '/posts/drafts'
 */
storeDraft.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeDraft.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\SocialPostController::storeDraft
 * @see app/Http/Controllers/SocialPostController.php:85
 * @route '/posts/drafts'
 */
    const storeDraftForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: storeDraft.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\SocialPostController::storeDraft
 * @see app/Http/Controllers/SocialPostController.php:85
 * @route '/posts/drafts'
 */
        storeDraftForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: storeDraft.url(options),
            method: 'post',
        })
    
    storeDraft.form = storeDraftForm
/**
* @see \App\Http\Controllers\SocialPostController::updateDraft
 * @see app/Http/Controllers/SocialPostController.php:98
 * @route '/posts/{post}/draft'
 */
export const updateDraft = (args: { post: number | { id: number } } | [post: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateDraft.url(args, options),
    method: 'put',
})

updateDraft.definition = {
    methods: ["put"],
    url: '/posts/{post}/draft',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\SocialPostController::updateDraft
 * @see app/Http/Controllers/SocialPostController.php:98
 * @route '/posts/{post}/draft'
 */
updateDraft.url = (args: { post: number | { id: number } } | [post: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { post: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { post: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    post: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        post: typeof args.post === 'object'
                ? args.post.id
                : args.post,
                }

    return updateDraft.definition.url
            .replace('{post}', parsedArgs.post.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SocialPostController::updateDraft
 * @see app/Http/Controllers/SocialPostController.php:98
 * @route '/posts/{post}/draft'
 */
updateDraft.put = (args: { post: number | { id: number } } | [post: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateDraft.url(args, options),
    method: 'put',
})

    /**
* @see \App\Http\Controllers\SocialPostController::updateDraft
 * @see app/Http/Controllers/SocialPostController.php:98
 * @route '/posts/{post}/draft'
 */
    const updateDraftForm = (args: { post: number | { id: number } } | [post: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: updateDraft.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'PUT',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\SocialPostController::updateDraft
 * @see app/Http/Controllers/SocialPostController.php:98
 * @route '/posts/{post}/draft'
 */
        updateDraftForm.put = (args: { post: number | { id: number } } | [post: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: updateDraft.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PUT',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    updateDraft.form = updateDraftForm
/**
* @see \App\Http\Controllers\SocialPostController::savePlatforms
 * @see app/Http/Controllers/SocialPostController.php:111
 * @route '/posts/{post}/platforms'
 */
export const savePlatforms = (args: { post: number | { id: number } } | [post: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: savePlatforms.url(args, options),
    method: 'post',
})

savePlatforms.definition = {
    methods: ["post"],
    url: '/posts/{post}/platforms',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SocialPostController::savePlatforms
 * @see app/Http/Controllers/SocialPostController.php:111
 * @route '/posts/{post}/platforms'
 */
savePlatforms.url = (args: { post: number | { id: number } } | [post: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { post: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { post: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    post: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        post: typeof args.post === 'object'
                ? args.post.id
                : args.post,
                }

    return savePlatforms.definition.url
            .replace('{post}', parsedArgs.post.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SocialPostController::savePlatforms
 * @see app/Http/Controllers/SocialPostController.php:111
 * @route '/posts/{post}/platforms'
 */
savePlatforms.post = (args: { post: number | { id: number } } | [post: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: savePlatforms.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\SocialPostController::savePlatforms
 * @see app/Http/Controllers/SocialPostController.php:111
 * @route '/posts/{post}/platforms'
 */
    const savePlatformsForm = (args: { post: number | { id: number } } | [post: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: savePlatforms.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\SocialPostController::savePlatforms
 * @see app/Http/Controllers/SocialPostController.php:111
 * @route '/posts/{post}/platforms'
 */
        savePlatformsForm.post = (args: { post: number | { id: number } } | [post: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: savePlatforms.url(args, options),
            method: 'post',
        })
    
    savePlatforms.form = savePlatformsForm
/**
* @see \App\Http\Controllers\SocialPostController::saveMedia
 * @see app/Http/Controllers/SocialPostController.php:132
 * @route '/posts/{post}/media'
 */
export const saveMedia = (args: { post: number | { id: number } } | [post: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: saveMedia.url(args, options),
    method: 'post',
})

saveMedia.definition = {
    methods: ["post"],
    url: '/posts/{post}/media',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SocialPostController::saveMedia
 * @see app/Http/Controllers/SocialPostController.php:132
 * @route '/posts/{post}/media'
 */
saveMedia.url = (args: { post: number | { id: number } } | [post: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { post: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { post: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    post: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        post: typeof args.post === 'object'
                ? args.post.id
                : args.post,
                }

    return saveMedia.definition.url
            .replace('{post}', parsedArgs.post.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SocialPostController::saveMedia
 * @see app/Http/Controllers/SocialPostController.php:132
 * @route '/posts/{post}/media'
 */
saveMedia.post = (args: { post: number | { id: number } } | [post: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: saveMedia.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\SocialPostController::saveMedia
 * @see app/Http/Controllers/SocialPostController.php:132
 * @route '/posts/{post}/media'
 */
    const saveMediaForm = (args: { post: number | { id: number } } | [post: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: saveMedia.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\SocialPostController::saveMedia
 * @see app/Http/Controllers/SocialPostController.php:132
 * @route '/posts/{post}/media'
 */
        saveMediaForm.post = (args: { post: number | { id: number } } | [post: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: saveMedia.url(args, options),
            method: 'post',
        })
    
    saveMedia.form = saveMediaForm
/**
* @see \App\Http\Controllers\SocialPostController::schedule
 * @see app/Http/Controllers/SocialPostController.php:154
 * @route '/posts/{post}/schedule'
 */
export const schedule = (args: { post: number | { id: number } } | [post: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: schedule.url(args, options),
    method: 'post',
})

schedule.definition = {
    methods: ["post"],
    url: '/posts/{post}/schedule',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SocialPostController::schedule
 * @see app/Http/Controllers/SocialPostController.php:154
 * @route '/posts/{post}/schedule'
 */
schedule.url = (args: { post: number | { id: number } } | [post: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { post: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { post: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    post: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        post: typeof args.post === 'object'
                ? args.post.id
                : args.post,
                }

    return schedule.definition.url
            .replace('{post}', parsedArgs.post.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SocialPostController::schedule
 * @see app/Http/Controllers/SocialPostController.php:154
 * @route '/posts/{post}/schedule'
 */
schedule.post = (args: { post: number | { id: number } } | [post: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: schedule.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\SocialPostController::schedule
 * @see app/Http/Controllers/SocialPostController.php:154
 * @route '/posts/{post}/schedule'
 */
    const scheduleForm = (args: { post: number | { id: number } } | [post: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: schedule.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\SocialPostController::schedule
 * @see app/Http/Controllers/SocialPostController.php:154
 * @route '/posts/{post}/schedule'
 */
        scheduleForm.post = (args: { post: number | { id: number } } | [post: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: schedule.url(args, options),
            method: 'post',
        })
    
    schedule.form = scheduleForm
const SocialPostController = { index, create, storeDraft, updateDraft, savePlatforms, saveMedia, schedule }

export default SocialPostController