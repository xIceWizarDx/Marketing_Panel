import React, { useState } from 'react';
import Icon from '../../../components/AppIcon';
import Image from '../../../components/AppImage';


const MediaGrid = ({ 
  items = [], 
  viewMode = 'grid', 
  selectedItems = [], 
  onItemSelect, 
  onItemClick,
  loading = false 
}) => {
  const [loadingItems, setLoadingItems] = useState(new Set());

  const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i))?.toFixed(2)) + ' ' + sizes?.[i];
  };

  const formatDate = (date) => {
    return new Intl.DateTimeFormat('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    })?.format(new Date(date));
  };

  const getFileTypeIcon = (type) => {
    if (type?.startsWith('image/')) return 'Image';
    if (type?.startsWith('video/')) return 'Video';
    return 'File';
  };

  const isSelected = (itemId) => selectedItems?.includes(itemId);

  if (loading) {
    return (
      <div className={`grid gap-4 ${
        viewMode === 'grid' ?'grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6' :'grid-cols-1'
      }`}>
        {Array.from({ length: 12 })?.map((_, index) => (
          <div key={index} className="animate-pulse">
            <div className={`bg-muted rounded-lg ${
              viewMode === 'grid' ? 'aspect-square' : 'h-20'
            }`} />
          </div>
        ))}
      </div>
    );
  }

  if (items?.length === 0) {
    return (
      <div className="text-center py-12">
        <div className="w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
          <Icon name="ImageOff" size={24} className="text-muted-foreground" />
        </div>
        <h3 className="font-body font-semibold text-lg text-foreground mb-2">
          Nenhuma mídia encontrada
        </h3>
        <p className="text-muted-foreground font-body text-sm">
          Faça upload de imagens e vídeos para começar
        </p>
      </div>
    );
  }

  if (viewMode === 'list') {
    return (
      <div className="space-y-2">
        {items?.map((item) => (
          <div
            key={item?.id}
            className={`flex items-center gap-4 p-4 rounded-lg border transition-smooth hover:bg-muted/50 cursor-pointer ${
              isSelected(item?.id) ? 'bg-accent/10 border-accent' : 'border-border'
            }`}
            onClick={() => onItemClick(item)}
          >
            <div className="relative">
              <input
                type="checkbox"
                checked={isSelected(item?.id)}
                onChange={(e) => {
                  e?.stopPropagation();
                  onItemSelect(item?.id);
                }}
                className="absolute top-2 left-2 z-10 w-4 h-4"
              />
              <div className="w-16 h-16 bg-muted rounded-lg overflow-hidden">
                {item?.type?.startsWith('image/') ? (
                  <Image
                    src={item?.url}
                    alt={item?.name}
                    className="w-full h-full object-cover"
                  />
                ) : (
                  <div className="w-full h-full flex items-center justify-center">
                    <Icon name={getFileTypeIcon(item?.type)} size={24} className="text-muted-foreground" />
                  </div>
                )}
              </div>
            </div>

            <div className="flex-1 min-w-0">
              <h4 className="font-body font-medium text-sm text-foreground truncate">
                {item?.name}
              </h4>
              <div className="flex items-center gap-4 mt-1 text-xs text-muted-foreground font-body">
                <span>{formatFileSize(item?.size)}</span>
                <span>{item?.dimensions}</span>
                <span>{formatDate(item?.uploadedAt)}</span>
              </div>
            </div>

            <div className="flex items-center gap-2">
              {item?.tags?.map((tag) => (
                <span
                  key={tag}
                  className="px-2 py-1 bg-muted text-muted-foreground text-xs rounded-md font-body"
                >
                  {tag}
                </span>
              ))}
            </div>
          </div>
        ))}
      </div>
    );
  }

  return (
    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
      {items?.map((item) => (
        <div
          key={item?.id}
          className={`group relative aspect-square bg-muted rounded-lg overflow-hidden cursor-pointer transition-smooth hover:ring-2 hover:ring-accent/50 ${
            isSelected(item?.id) ? 'ring-2 ring-accent' : ''
          }`}
          onClick={() => onItemClick(item)}
        >
          {/* Selection Checkbox */}
          <div className="absolute top-2 left-2 z-10">
            <input
              type="checkbox"
              checked={isSelected(item?.id)}
              onChange={(e) => {
                e?.stopPropagation();
                onItemSelect(item?.id);
              }}
              className="w-4 h-4 rounded border-2 border-white bg-white/80 backdrop-blur-sm"
            />
          </div>

          {/* Media Content */}
          {item?.type?.startsWith('image/') ? (
            <Image
              src={item?.url}
              alt={item?.name}
              className="w-full h-full object-cover"
            />
          ) : (
            <div className="w-full h-full flex items-center justify-center bg-muted">
              <Icon name={getFileTypeIcon(item?.type)} size={32} className="text-muted-foreground" />
            </div>
          )}

          {/* Overlay Information */}
          <div className="absolute inset-0 bg-black/0 group-hover:bg-black/50 transition-smooth">
            <div className="absolute bottom-0 left-0 right-0 p-3 bg-gradient-to-t from-black/80 to-transparent opacity-0 group-hover:opacity-100 transition-smooth">
              <h4 className="font-body font-medium text-sm text-white truncate mb-1">
                {item?.name}
              </h4>
              <div className="flex items-center justify-between text-xs text-white/80 font-body">
                <span>{formatFileSize(item?.size)}</span>
                <span>{item?.dimensions}</span>
              </div>
            </div>
          </div>

          {/* File Type Badge */}
          <div className="absolute top-2 right-2">
            <div className="w-6 h-6 bg-black/50 backdrop-blur-sm rounded-full flex items-center justify-center">
              <Icon name={getFileTypeIcon(item?.type)} size={12} color="white" />
            </div>
          </div>
        </div>
      ))}
    </div>
  );
};

export default MediaGrid;