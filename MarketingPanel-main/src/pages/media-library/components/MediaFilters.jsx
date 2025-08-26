import React, { useState } from 'react';
import Icon from '../../../components/AppIcon';
import Button from '../../../components/ui/Button';
import Input from '../../../components/ui/Input';
import { Checkbox } from '../../../components/ui/Checkbox';

const MediaFilters = ({ 
  isOpen, 
  onClose, 
  filters, 
  onFiltersChange,
  isMobile = false 
}) => {
  const [localFilters, setLocalFilters] = useState(filters);

  const fileTypes = [
    { id: 'image', label: 'Imagens', count: 245 },
    { id: 'video', label: 'Vídeos', count: 32 },
    { id: 'gif', label: 'GIFs', count: 18 }
  ];

  const dateRanges = [
    { id: 'today', label: 'Hoje' },
    { id: 'week', label: 'Esta semana' },
    { id: 'month', label: 'Este mês' },
    { id: 'year', label: 'Este ano' },
    { id: 'custom', label: 'Período personalizado' }
  ];

  const dimensions = [
    { id: 'square', label: 'Quadrado (1:1)' },
    { id: 'landscape', label: 'Paisagem (16:9)' },
    { id: 'portrait', label: 'Retrato (9:16)' },
    { id: 'story', label: 'Stories (9:16)' }
  ];

  const handleFilterChange = (category, value, checked) => {
    const newFilters = { ...localFilters };
    
    if (category === 'fileTypes') {
      if (checked) {
        newFilters.fileTypes = [...(newFilters?.fileTypes || []), value];
      } else {
        newFilters.fileTypes = (newFilters?.fileTypes || [])?.filter(type => type !== value);
      }
    } else if (category === 'dateRange') {
      newFilters.dateRange = value;
    } else if (category === 'dimensions') {
      if (checked) {
        newFilters.dimensions = [...(newFilters?.dimensions || []), value];
      } else {
        newFilters.dimensions = (newFilters?.dimensions || [])?.filter(dim => dim !== value);
      }
    } else {
      newFilters[category] = value;
    }
    
    setLocalFilters(newFilters);
  };

  const applyFilters = () => {
    onFiltersChange(localFilters);
    if (isMobile) onClose();
  };

  const clearFilters = () => {
    const clearedFilters = {
      fileTypes: [],
      dateRange: '',
      dimensions: [],
      tags: '',
      minSize: '',
      maxSize: ''
    };
    setLocalFilters(clearedFilters);
    onFiltersChange(clearedFilters);
  };

  const filterContent = (
    <div className="space-y-6">
      {/* File Types */}
      <div>
        <h3 className="font-body font-semibold text-sm text-foreground mb-3">
          Tipo de Arquivo
        </h3>
        <div className="space-y-2">
          {fileTypes?.map((type) => (
            <div key={type?.id} className="flex items-center justify-between">
              <Checkbox
                label={type?.label}
                checked={(localFilters?.fileTypes || [])?.includes(type?.id)}
                onChange={(e) => handleFilterChange('fileTypes', type?.id, e?.target?.checked)}
              />
              <span className="text-xs text-muted-foreground font-body">
                {type?.count}
              </span>
            </div>
          ))}
        </div>
      </div>

      {/* Date Range */}
      <div>
        <h3 className="font-body font-semibold text-sm text-foreground mb-3">
          Data de Upload
        </h3>
        <div className="space-y-2">
          {dateRanges?.map((range) => (
            <div key={range?.id} className="flex items-center">
              <input
                type="radio"
                id={range?.id}
                name="dateRange"
                checked={localFilters?.dateRange === range?.id}
                onChange={() => handleFilterChange('dateRange', range?.id)}
                className="w-4 h-4 text-accent"
              />
              <label
                htmlFor={range?.id}
                className="ml-2 text-sm font-body text-foreground cursor-pointer"
              >
                {range?.label}
              </label>
            </div>
          ))}
        </div>
      </div>

      {/* Dimensions */}
      <div>
        <h3 className="font-body font-semibold text-sm text-foreground mb-3">
          Proporções
        </h3>
        <div className="space-y-2">
          {dimensions?.map((dimension) => (
            <Checkbox
              key={dimension?.id}
              label={dimension?.label}
              checked={(localFilters?.dimensions || [])?.includes(dimension?.id)}
              onChange={(e) => handleFilterChange('dimensions', dimension?.id, e?.target?.checked)}
            />
          ))}
        </div>
      </div>

      {/* Tags */}
      <div>
        <h3 className="font-body font-semibold text-sm text-foreground mb-3">
          Tags
        </h3>
        <Input
          type="text"
          placeholder="Buscar por tags..."
          value={localFilters?.tags || ''}
          onChange={(e) => handleFilterChange('tags', e?.target?.value)}
        />
      </div>

      {/* File Size */}
      <div>
        <h3 className="font-body font-semibold text-sm text-foreground mb-3">
          Tamanho do Arquivo
        </h3>
        <div className="grid grid-cols-2 gap-2">
          <Input
            type="number"
            placeholder="Min (MB)"
            value={localFilters?.minSize || ''}
            onChange={(e) => handleFilterChange('minSize', e?.target?.value)}
          />
          <Input
            type="number"
            placeholder="Max (MB)"
            value={localFilters?.maxSize || ''}
            onChange={(e) => handleFilterChange('maxSize', e?.target?.value)}
          />
        </div>
      </div>

      {/* Actions */}
      <div className="flex gap-2 pt-4 border-t border-border">
        <Button
          variant="outline"
          onClick={clearFilters}
          className="flex-1"
        >
          Limpar
        </Button>
        <Button
          variant="default"
          onClick={applyFilters}
          className="flex-1"
        >
          Aplicar
        </Button>
      </div>
    </div>
  );

  if (isMobile) {
    return (
      <>
        {/* Mobile Backdrop */}
        {isOpen && (
          <div
            className="fixed inset-0 bg-black/50 z-40 lg:hidden"
            onClick={onClose}
          />
        )}

        {/* Mobile Drawer */}
        <div
          className={`fixed right-0 top-0 h-full w-80 max-w-[90vw] bg-card border-l border-border z-50 lg:hidden transition-drawer transform ${
            isOpen ? 'translate-x-0' : 'translate-x-full'
          }`}
        >
          <div className="flex items-center justify-between p-4 border-b border-border">
            <h2 className="font-body font-semibold text-lg text-foreground">
              Filtros
            </h2>
            <Button
              variant="ghost"
              size="icon"
              onClick={onClose}
            >
              <Icon name="X" size={20} />
            </Button>
          </div>
          <div className="p-4 overflow-y-auto h-full pb-20">
            {filterContent}
          </div>
        </div>
      </>
    );
  }

  // Desktop Sidebar
  return (
    <div className="w-64 bg-card border border-border rounded-lg p-4 h-fit sticky top-20">
      <div className="flex items-center justify-between mb-4">
        <h2 className="font-body font-semibold text-lg text-foreground">
          Filtros
        </h2>
        <Button
          variant="ghost"
          size="sm"
          onClick={clearFilters}
          className="text-xs"
        >
          Limpar tudo
        </Button>
      </div>
      {filterContent}
    </div>
  );
};

export default MediaFilters;