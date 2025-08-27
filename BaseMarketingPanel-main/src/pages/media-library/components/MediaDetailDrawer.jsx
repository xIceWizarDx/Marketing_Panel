import React, { useState } from 'react';
import Icon from '../../../components/AppIcon';
import Image from '../../../components/AppImage';
import Button from '../../../components/ui/Button';
import Input from '../../../components/ui/Input';

const MediaDetailDrawer = ({ 
  item, 
  isOpen, 
  onClose, 
  onUpdate, 
  onDelete,
  isMobile = false 
}) => {
  const [isEditing, setIsEditing] = useState(false);
  const [editData, setEditData] = useState({
    name: item?.name || '',
    tags: item?.tags?.join(', ') || '',
    description: item?.description || ''
  });

  if (!item) return null;

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
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })?.format(new Date(date));
  };

  const handleSave = () => {
    const updatedItem = {
      ...item,
      name: editData?.name,
      tags: editData?.tags?.split(',')?.map(tag => tag?.trim())?.filter(Boolean),
      description: editData?.description
    };
    onUpdate(updatedItem);
    setIsEditing(false);
  };

  const handleCancel = () => {
    setEditData({
      name: item?.name,
      tags: item?.tags?.join(', ') || '',
      description: item?.description || ''
    });
    setIsEditing(false);
  };

  const cropRatios = [
    { id: 'square', label: 'Quadrado', ratio: '1:1', icon: 'Square' },
    { id: 'landscape', label: 'Paisagem', ratio: '16:9', icon: 'Rectangle' },
    { id: 'portrait', label: 'Retrato', ratio: '9:16', icon: 'Smartphone' },
    { id: 'story', label: 'Stories', ratio: '9:16', icon: 'Smartphone' }
  ];

  const usageHistory = [
    {
      id: 1,
      platform: 'Instagram',
      postTitle: 'Promoção de Verão 2025',
      publishedAt: '2025-01-20T10:30:00Z',
      engagement: '1.2K curtidas'
    },
    {
      id: 2,
      platform: 'Facebook',
      postTitle: 'Novidades da Semana',
      publishedAt: '2025-01-18T14:15:00Z',
      engagement: '856 curtidas'
    }
  ];

  const drawerContent = (
    <div className="flex flex-col h-full">
      {/* Header */}
      <div className="flex items-center justify-between p-4 border-b border-border">
        <h2 className="font-body font-semibold text-lg text-foreground">
          Detalhes da Mídia
        </h2>
        <div className="flex items-center gap-2">
          {!isEditing && (
            <Button
              variant="ghost"
              size="icon"
              onClick={() => setIsEditing(true)}
              aria-label="Editar mídia"
            >
              <Icon name="Edit2" size={18} />
            </Button>
          )}
          <Button
            variant="ghost"
            size="icon"
            onClick={onClose}
            aria-label="Fechar detalhes"
          >
            <Icon name="X" size={20} />
          </Button>
        </div>
      </div>

      {/* Content */}
      <div className="flex-1 overflow-y-auto p-4 space-y-6">
        {/* Media Preview */}
        <div className="aspect-square bg-muted rounded-lg overflow-hidden">
          {item?.type?.startsWith('image/') ? (
            <Image
              src={item?.url}
              alt={item?.name}
              className="w-full h-full object-cover"
            />
          ) : (
            <div className="w-full h-full flex items-center justify-center">
              <Icon name="Video" size={48} className="text-muted-foreground" />
            </div>
          )}
        </div>

        {/* Basic Information */}
        <div className="space-y-4">
          {isEditing ? (
            <>
              <Input
                label="Nome do arquivo"
                value={editData?.name}
                onChange={(e) => setEditData({ ...editData, name: e?.target?.value })}
              />
              <Input
                label="Tags (separadas por vírgula)"
                value={editData?.tags}
                onChange={(e) => setEditData({ ...editData, tags: e?.target?.value })}
                placeholder="marketing, promoção, verão"
              />
              <div>
                <label className="block text-sm font-body font-medium text-foreground mb-2">
                  Descrição
                </label>
                <textarea
                  value={editData?.description}
                  onChange={(e) => setEditData({ ...editData, description: e?.target?.value })}
                  className="w-full p-3 border border-border rounded-md resize-none h-20 text-sm font-body"
                  placeholder="Adicione uma descrição..."
                />
              </div>
            </>
          ) : (
            <>
              <div>
                <h3 className="font-body font-semibold text-base text-foreground mb-2">
                  {item?.name}
                </h3>
                {item?.description && (
                  <p className="text-sm text-muted-foreground font-body">
                    {item?.description}
                  </p>
                )}
              </div>

              {item?.tags && item?.tags?.length > 0 && (
                <div>
                  <h4 className="font-body font-medium text-sm text-foreground mb-2">
                    Tags
                  </h4>
                  <div className="flex flex-wrap gap-2">
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
              )}
            </>
          )}
        </div>

        {/* Edit Actions */}
        {isEditing && (
          <div className="flex gap-2">
            <Button
              variant="outline"
              onClick={handleCancel}
              className="flex-1"
            >
              Cancelar
            </Button>
            <Button
              variant="default"
              onClick={handleSave}
              className="flex-1"
            >
              Salvar
            </Button>
          </div>
        )}

        {/* File Details */}
        <div>
          <h4 className="font-body font-medium text-sm text-foreground mb-3">
            Informações do Arquivo
          </h4>
          <div className="space-y-2 text-sm font-body">
            <div className="flex justify-between">
              <span className="text-muted-foreground">Tipo:</span>
              <span className="text-foreground">{item?.type}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-muted-foreground">Tamanho:</span>
              <span className="text-foreground">{formatFileSize(item?.size)}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-muted-foreground">Dimensões:</span>
              <span className="text-foreground">{item?.dimensions}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-muted-foreground">Upload:</span>
              <span className="text-foreground">{formatDate(item?.uploadedAt)}</span>
            </div>
          </div>
        </div>

        {/* Crop Options */}
        {item?.type?.startsWith('image/') && (
          <div>
            <h4 className="font-body font-medium text-sm text-foreground mb-3">
              Proporções para Plataformas
            </h4>
            <div className="grid grid-cols-2 gap-2">
              {cropRatios?.map((ratio) => (
                <Button
                  key={ratio?.id}
                  variant="outline"
                  size="sm"
                  className="flex items-center gap-2 justify-start"
                  onClick={() => {
                    // Handle crop action
                    console.log('Crop to', ratio?.ratio);
                  }}
                >
                  <Icon name={ratio?.icon} size={16} />
                  <div className="text-left">
                    <div className="text-xs font-medium">{ratio?.label}</div>
                    <div className="text-xs text-muted-foreground">{ratio?.ratio}</div>
                  </div>
                </Button>
              ))}
            </div>
          </div>
        )}

        {/* Usage History */}
        <div>
          <h4 className="font-body font-medium text-sm text-foreground mb-3">
            Histórico de Uso
          </h4>
          {usageHistory?.length > 0 ? (
            <div className="space-y-3">
              {usageHistory?.map((usage) => (
                <div
                  key={usage?.id}
                  className="p-3 bg-muted rounded-lg"
                >
                  <div className="flex items-center gap-2 mb-1">
                    <Icon name="ExternalLink" size={14} className="text-muted-foreground" />
                    <span className="font-body font-medium text-sm text-foreground">
                      {usage?.platform}
                    </span>
                  </div>
                  <p className="text-sm text-foreground font-body mb-1">
                    {usage?.postTitle}
                  </p>
                  <div className="flex justify-between text-xs text-muted-foreground font-body">
                    <span>{formatDate(usage?.publishedAt)}</span>
                    <span>{usage?.engagement}</span>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <p className="text-sm text-muted-foreground font-body">
              Esta mídia ainda não foi utilizada em nenhuma publicação.
            </p>
          )}
        </div>
      </div>

      {/* Footer Actions */}
      {!isEditing && (
        <div className="p-4 border-t border-border">
          <div className="flex gap-2">
            <Button
              variant="outline"
              onClick={() => {
                // Handle download
                const link = document.createElement('a');
                link.href = item?.url;
                link.download = item?.name;
                link?.click();
              }}
              iconName="Download"
              iconPosition="left"
              className="flex-1"
            >
              Baixar
            </Button>
            <Button
              variant="destructive"
              onClick={() => onDelete(item?.id)}
              iconName="Trash2"
              iconPosition="left"
              className="flex-1"
            >
              Excluir
            </Button>
          </div>
        </div>
      )}
    </div>
  );

  if (isMobile) {
    return (
      <>
        {/* Mobile Backdrop */}
        {isOpen && (
          <div
            className="fixed inset-0 bg-black/50 z-40"
            onClick={onClose}
          />
        )}

        {/* Mobile Drawer */}
        <div
          className={`fixed right-0 top-0 h-full w-full max-w-md bg-card z-50 transition-drawer transform ${
            isOpen ? 'translate-x-0' : 'translate-x-full'
          }`}
        >
          {drawerContent}
        </div>
      </>
    );
  }

  // Desktop Drawer
  return (
    <>
      {isOpen && (
        <div
          className="fixed inset-0 bg-black/50 z-40"
          onClick={onClose}
        />
      )}
      <div
        className={`fixed right-0 top-0 h-full w-96 bg-card border-l border-border z-50 transition-drawer transform ${
          isOpen ? 'translate-x-0' : 'translate-x-full'
        }`}
      >
        {drawerContent}
      </div>
    </>
  );
};

export default MediaDetailDrawer;