import React, { useState, useRef } from 'react';
import Icon from '../../../components/AppIcon';
import Button from '../../../components/ui/Button';

const MediaUploadArea = ({ onUpload, isUploading = false }) => {
  const [isDragOver, setIsDragOver] = useState(false);
  const fileInputRef = useRef(null);

  const handleDragOver = (e) => {
    e?.preventDefault();
    setIsDragOver(true);
  };

  const handleDragLeave = (e) => {
    e?.preventDefault();
    setIsDragOver(false);
  };

  const handleDrop = (e) => {
    e?.preventDefault();
    setIsDragOver(false);
    const files = Array.from(e?.dataTransfer?.files);
    handleFiles(files);
  };

  const handleFileSelect = (e) => {
    const files = Array.from(e?.target?.files);
    handleFiles(files);
  };

  const handleFiles = (files) => {
    const validFiles = files?.filter(file => {
      const isValidType = file?.type?.startsWith('image/') || file?.type?.startsWith('video/');
      const isValidSize = file?.size <= 50 * 1024 * 1024; // 50MB limit
      return isValidType && isValidSize;
    });

    if (validFiles?.length > 0) {
      onUpload(validFiles);
    }
  };

  const openFileDialog = () => {
    fileInputRef?.current?.click();
  };

  return (
    <div
      className={`border-2 border-dashed rounded-lg p-8 text-center transition-smooth ${
        isDragOver
          ? 'border-accent bg-accent/5' :'border-border hover:border-accent/50'
      }`}
      onDragOver={handleDragOver}
      onDragLeave={handleDragLeave}
      onDrop={handleDrop}
    >
      <input
        ref={fileInputRef}
        type="file"
        multiple
        accept="image/*,video/*"
        onChange={handleFileSelect}
        className="hidden"
      />

      <div className="flex flex-col items-center gap-4">
        <div className={`w-16 h-16 rounded-full flex items-center justify-center ${
          isDragOver ? 'bg-accent text-accent-foreground' : 'bg-muted text-muted-foreground'
        }`}>
          <Icon name={isUploading ? 'Loader2' : 'Upload'} size={24} className={isUploading ? 'animate-spin' : ''} />
        </div>

        <div>
          <h3 className="font-body font-semibold text-lg text-foreground mb-2">
            {isDragOver ? 'Solte os arquivos aqui' : 'Envie suas mídias'}
          </h3>
          <p className="text-muted-foreground font-body text-sm mb-4">
            Arraste e solte ou clique para selecionar imagens e vídeos
          </p>
          <p className="text-xs text-muted-foreground font-body">
            Suporte para JPG, PNG, GIF, MP4, MOV até 50MB
          </p>
        </div>

        <Button
          variant="outline"
          onClick={openFileDialog}
          disabled={isUploading}
          iconName="FolderOpen"
          iconPosition="left"
        >
          {isUploading ? 'Enviando...' : 'Selecionar Arquivos'}
        </Button>
      </div>
    </div>
  );
};

export default MediaUploadArea;