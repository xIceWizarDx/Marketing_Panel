import React from 'react';

const DashboardSkeleton = () => {
  return (
    <div className="space-y-6">
      {/* Welcome Section Skeleton */}
      <div className="border-b border-border pb-6">
        <div className="max-w-3xl space-y-3">
          <div className="h-8 bg-muted rounded-lg w-64 animate-pulse" />
          <div className="h-4 bg-muted rounded w-full animate-pulse" />
          <div className="h-4 bg-muted rounded w-3/4 animate-pulse" />
        </div>
      </div>

      {/* Mobile Layout Skeleton */}
      <div className="lg:hidden space-y-6">
        {/* Platform Status Cards */}
        <div>
          <div className="h-6 bg-muted rounded w-48 mb-4 animate-pulse" />
          <div className="space-y-3">
            {Array?.from({ length: 6 })?.map((_, index) => (
              <div key={index} className="bg-card border border-border rounded-lg p-4">
                <div className="flex items-center justify-between mb-3">
                  <div className="flex items-center gap-3">
                    <div className="w-8 h-8 bg-muted rounded-lg animate-pulse" />
                    <div className="h-4 bg-muted rounded w-24 animate-pulse" />
                  </div>
                  <div className="h-6 bg-muted rounded-full w-20 animate-pulse" />
                </div>
                <div className="flex justify-between">
                  <div className="h-3 bg-muted rounded w-16 animate-pulse" />
                  <div className="h-3 bg-muted rounded w-16 animate-pulse" />
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Metrics Skeleton */}
        <div>
          <div className="h-6 bg-muted rounded w-40 mb-4 animate-pulse" />
          <div className="grid grid-cols-2 gap-3">
            {Array?.from({ length: 4 })?.map((_, index) => (
              <div key={index} className="bg-card border border-border rounded-lg p-4">
                <div className="flex items-center justify-between mb-2">
                  <div className="w-8 h-8 bg-muted rounded-lg animate-pulse" />
                  <div className="h-4 bg-muted rounded w-12 animate-pulse" />
                </div>
                <div className="h-6 bg-muted rounded w-16 mb-1 animate-pulse" />
                <div className="h-3 bg-muted rounded w-full animate-pulse" />
              </div>
            ))}
          </div>
        </div>

        {/* Activity Feed Skeleton */}
        <div>
          <div className="h-6 bg-muted rounded w-44 mb-4 animate-pulse" />
          <div className="bg-card border border-border rounded-lg p-4">
            <div className="space-y-4">
              {Array?.from({ length: 5 })?.map((_, index) => (
                <div key={index} className={`flex gap-3 ${index !== 4 ? 'pb-4 border-b border-border' : ''}`}>
                  <div className="w-8 h-8 bg-muted rounded-lg animate-pulse" />
                  <div className="flex-1">
                    <div className="h-4 bg-muted rounded w-3/4 mb-2 animate-pulse" />
                    <div className="h-3 bg-muted rounded w-full mb-2 animate-pulse" />
                    <div className="h-3 bg-muted rounded w-20 animate-pulse" />
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>

      {/* Desktop Layout Skeleton */}
      <div className="hidden lg:block">
        <div className="grid grid-cols-12 gap-6">
          {/* Left Column */}
          <div className="col-span-8 space-y-6">
            {/* Platform Status Grid */}
            <div>
              <div className="h-7 bg-muted rounded w-48 mb-4 animate-pulse" />
              <div className="grid grid-cols-2 xl:grid-cols-3 gap-4">
                {Array?.from({ length: 6 })?.map((_, index) => (
                  <div key={index} className="bg-card border border-border rounded-lg p-6">
                    <div className="flex items-center justify-between mb-4">
                      <div className="flex items-center gap-4">
                        <div className="w-12 h-12 bg-muted rounded-lg animate-pulse" />
                        <div className="space-y-2">
                          <div className="h-4 bg-muted rounded w-24 animate-pulse" />
                          <div className="h-3 bg-muted rounded w-32 animate-pulse" />
                        </div>
                      </div>
                      <div className="h-7 bg-muted rounded-full w-20 animate-pulse" />
                    </div>
                    <div className="h-16 bg-muted/30 rounded-lg animate-pulse" />
                  </div>
                ))}
              </div>
            </div>

            {/* Activity Feed */}
            <div>
              <div className="h-7 bg-muted rounded w-44 mb-4 animate-pulse" />
              <div className="bg-card border border-border rounded-lg p-6">
                <div className="space-y-4">
                  {Array?.from({ length: 8 })?.map((_, index) => (
                    <div key={index} className={`flex gap-3 ${index !== 7 ? 'pb-4 border-b border-border' : ''}`}>
                      <div className="w-8 h-8 bg-muted rounded-lg animate-pulse" />
                      <div className="flex-1">
                        <div className="h-4 bg-muted rounded w-3/4 mb-2 animate-pulse" />
                        <div className="h-3 bg-muted rounded w-full mb-2 animate-pulse" />
                        <div className="h-3 bg-muted rounded w-24 animate-pulse" />
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>

          {/* Right Sidebar */}
          <div className="col-span-4 space-y-6">
            <div>
              <div className="h-7 bg-muted rounded w-40 mb-4 animate-pulse" />
              <div className="space-y-4">
                {Array?.from({ length: 6 })?.map((_, index) => (
                  <div key={index} className="bg-card border border-border rounded-lg p-6">
                    <div className="flex items-center justify-between mb-4">
                      <div className="flex items-center gap-3">
                        <div className="w-10 h-10 bg-muted rounded-lg animate-pulse" />
                        <div className="h-4 bg-muted rounded w-32 animate-pulse" />
                      </div>
                      <div className="h-4 bg-muted rounded w-12 animate-pulse" />
                    </div>
                    <div className="h-8 bg-muted rounded w-24 mb-2 animate-pulse" />
                    <div className="h-3 bg-muted rounded w-full animate-pulse" />
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default DashboardSkeleton;