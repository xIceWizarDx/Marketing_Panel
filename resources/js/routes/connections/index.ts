import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\PlatformConnectionController::index
 * @see app/Http/Controllers/PlatformConnectionController.php:11
 * @route '/connections'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/connections',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PlatformConnectionController::index
 * @see app/Http/Controllers/PlatformConnectionController.php:11
 * @route '/connections'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PlatformConnectionController::index
 * @see app/Http/Controllers/PlatformConnectionController.php:11
 * @route '/connections'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PlatformConnectionController::index
 * @see app/Http/Controllers/PlatformConnectionController.php:11
 * @route '/connections'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\PlatformConnectionController::index
 * @see app/Http/Controllers/PlatformConnectionController.php:11
 * @route '/connections'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\PlatformConnectionController::index
 * @see app/Http/Controllers/PlatformConnectionController.php:11
 * @route '/connections'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\PlatformConnectionController::index
 * @see app/Http/Controllers/PlatformConnectionController.php:11
 * @route '/connections'
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
* @see \App\Http\Controllers\PlatformConnectionController::status
 * @see app/Http/Controllers/PlatformConnectionController.php:48
 * @route '/connections/{account}/status'
 */
export const status = (args: { account: number | { id: number } } | [account: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: status.url(args, options),
    method: 'post',
})

status.definition = {
    methods: ["post"],
    url: '/connections/{account}/status',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PlatformConnectionController::status
 * @see app/Http/Controllers/PlatformConnectionController.php:48
 * @route '/connections/{account}/status'
 */
status.url = (args: { account: number | { id: number } } | [account: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { account: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { account: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    account: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        account: typeof args.account === 'object'
                ? args.account.id
                : args.account,
                }

    return status.definition.url
            .replace('{account}', parsedArgs.account.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PlatformConnectionController::status
 * @see app/Http/Controllers/PlatformConnectionController.php:48
 * @route '/connections/{account}/status'
 */
status.post = (args: { account: number | { id: number } } | [account: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: status.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\PlatformConnectionController::status
 * @see app/Http/Controllers/PlatformConnectionController.php:48
 * @route '/connections/{account}/status'
 */
    const statusForm = (args: { account: number | { id: number } } | [account: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: status.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\PlatformConnectionController::status
 * @see app/Http/Controllers/PlatformConnectionController.php:48
 * @route '/connections/{account}/status'
 */
        statusForm.post = (args: { account: number | { id: number } } | [account: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: status.url(args, options),
            method: 'post',
        })
    
    status.form = statusForm
const connections = {
    index,
status,
}

export default connections