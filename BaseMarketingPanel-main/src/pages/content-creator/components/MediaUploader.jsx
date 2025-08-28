import React, { useState, useRef } from 'react';
import Icon from '../../../components/AppIcon';
import Button from '../../../components/ui/Button';
import Image from '../../../components/AppImage';

const MediaUploader = ({ uploadedMedia, onMediaUpload, onMediaRemove, selectedPlatforms }) => {
  const [dragActive, setDragActive] = useState(false);
  const fileInputRef = useRef(null);

  const handleDrag = (e) => {
    e?.preventDefault();
    e?.stopPropagation();
    if (e?.type === 'dragenter' || e?.type === 'dragover') {
      setDragActive(true);
    } else if (e?.type === 'dragleave') {
      setDragActive(false);
    }
  };

  const handleDrop = (e) => {
    e?.preventDefault();
    e?.stopPropagation();
    setDragActive(false);

    if (e?.dataTransfer?.files && e?.dataTransfer?.files?.[0]) {
      handleFiles(e?.dataTransfer?.files);
    }
  };

  const handleFiles = (files) => {
    Array.from(files)?.forEach(file => {
      if (file?.type?.startsWith('image/') || file?.type?.startsWith('video/')) {
        const reader = new FileReader();
        reader.onload = (e) => {
          const mediaItem = {
            id: Date.now() + Math.random(),
            file,
            url: e?.target?.result,
            type: file?.type?.startsWith('image/') ? 'image' : 'video',
            name: file?.name,
            size: file?.size
          };
          onMediaUpload(mediaItem);
        };
        reader?.readAsDataURL(file);
      }
    });
  };

  const handleFileSelect = (e) => {
    if (e?.target?.files) {
      handleFiles(e?.target?.files);
    }
  };

  const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i))?.toFixed(2)) + ' ' + sizes?.[i];
  };

  const cropRatios = {
    instagram: [
      { label: 'Quadrado (1:1)', value: '1:1' },
      { label: 'Retrato (4:5)', value: '4:5' },
      { label: 'Paisagem (16:9)', value: '16:9' }
    ],
    facebook: [
      { label: 'Quadrado (1:1)', value: '1:1' },
      { label: 'Paisagem (16:9)', value: '16:9' }
    ],
    youtube: [
      { label: 'Thumbnail (16:9)', value: '16:9' }
    ],
    tiktok: [
      { label: 'Vertical (9:16)', value: '9:16' }
    ],
    twitter: [
      { label: 'Paisagem (16:9)', value: '16:9' },
      { label: 'Quadrado (1:1)', value: '1:1' }
    ]
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h3 className="text-lg font-body font-bold text-foreground">
          Adicionar Mídia
        </h3>
        <span className="text-sm text-muted-foreground font-body">
          {uploadedMedia?.length} arquivo(s)
        </span>
      </div>
      {/* Upload Area */}
      <div
        className={`relative border-2 border-dashed rounded-lg p-8 text-center transition-smooth ${
          dragActive
            ? 'border-accent bg-accent/5' :'border-border hover:border-accent/50'
        }`}
        onDragEnter={handleDrag}
        onDragLeave={handleDrag}
        onDragOver={handleDrag}
        onDrop={handleDrop}
      >
        <input
          ref={fileInputRef}
          type="file"
          multiple
          accept="image/*,video/*"
          onChange={handleFileSelect}
          className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
        />

        <div className="space-y-4">
          <div className="w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto">
            <Icon name="Upload" size={32} className="text-muted-foreground" />
          </div>

          <div>
            <p className="text-base font-body font-medium text-foreground mb-2">
              Arraste arquivos aqui ou clique para selecionar
            </p>
            <p className="text-sm text-muted-foreground font-body">
              Suporte para imagens (JPG, PNG, GIF) e vídeos (MP4, MOV) até 100MB
            </p>
          </div>

          <Button
            variant="outline"
            onClick={() => fileInputRef?.current?.click()}
            iconName="FolderOpen"
            iconPosition="left"
          >
            Selecionar Arquivos
          </Button>
        </div>
      </div>
      {/* Uploaded Media Grid */}
      {uploadedMedia?.length > 0 && (
        <div className="space-y-4">
          <h4 className="text-base font-body font-medium text-foreground">
            Arquivos Carregados
          </h4>

          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            {uploadedMedia?.map((media) => (
              <div key={media?.id} className="bg-card border border-border rounded-lg overflow-hidden">
                <div className="aspect-video bg-muted relative overflow-hidden">
                  {media?.type === 'image' ? (
                    <Image
                      src={media?.url}
                      alt={media?.name}
                      className="w-full h-full object-cover"
                    />
                  ) : (
                    <div className="w-full h-full flex items-center justify-center bg-muted">
                      <Icon name="Play" size={32} className="text-muted-foreground" />
                    </div>
                  )}

                  <Button
                    variant="destructive"
                    size="icon"
                    className="absolute top-2 right-2 w-8 h-8"
                    onClick={() => onMediaRemove(media?.id)}
                  >
                    <Icon name="X" size={16} />
                  </Button>
                </div>

                <div className="p-3">
                  <p className="text-sm font-body font-medium text-foreground truncate mb-1">
                    {media?.name}
                  </p>
                  <p className="text-xs text-muted-foreground font-body mb-3">
                    {formatFileSize(media?.size)}
                  </p>

                  {/* Crop Ratios for Selected Platforms */}
                  {selectedPlatforms?.length > 0 && (
                    <div className="space-y-2">
                      <p className="text-xs font-body text-muted-foreground">
                        Proporções recomendadas:
                      </p>
                      {selectedPlatforms?.map(platformId => {
                        const ratios = cropRatios?.[platformId];
                        if (!ratios) return null;
                        
                        return (
                          <div key={platformId} className="text-xs font-body">
                            <span className="text-foreground font-medium capitalize">
                              {platformId}:
                            </span>
                            <span className="text-muted-foreground ml-1">
                              {ratios?.map(r => r?.label)?.join(', ')}
                            </span>
                          </div>
                        );
                      })}
                    </div>
                  )}
                </div>
              </div>
            ))}
          </div>
        </div>
      )}
      {/* Platform-specific Guidelines */}
      {selectedPlatforms?.length > 0 && (
        <div className="bg-accent/5 border border-accent/20 rounded-lg p-4">
          <div className="flex items-start gap-3">
            <Icon name="Info" size={20} className="text-accent flex-shrink-0 mt-0.5" />
            <div>
              <h4 className="text-sm font-body font-medium text-foreground mb-2">
                Diretrizes das Plataformas
              </h4>
              <ul className="text-xs text-muted-foreground font-body space-y-1">
                {selectedPlatforms?.includes('instagram') && (
                  <li>• Instagram: Máx. 10 imagens por post, vídeos até 60s</li>
                )}
                {selectedPlatforms?.includes('facebook') && (
                  <li>• Facebook: Sem limite de imagens, vídeos até 240min</li>
                )}
                {selectedPlatforms?.includes('youtube') && (
                  <li>• YouTube: Thumbnail obrigatório, vídeos até 12h</li>
                )}
                {selectedPlatforms?.includes('tiktok') && (
                  <li>• TikTok: Apenas vídeos, duração 15s-10min</li>
                )}
                {selectedPlatforms?.includes('twitter') && (
                  <li>• X (Twitter): Máx. 4 imagens, vídeos até 2min20s</li>
                )}
              </ul>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default MediaUploader;