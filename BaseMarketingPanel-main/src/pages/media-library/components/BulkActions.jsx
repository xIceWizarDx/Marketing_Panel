import React, { useState } from 'react';
import Icon from '../../../components/AppIcon';
import Button from '../../../components/ui/Button';
import Input from '../../../components/ui/Input';

const BulkActions = ({ 
  selectedItems = [], 
  onClearSelection, 
  onBulkDelete, 
  onBulkTag,
  onBulkMove 
}) => {
  const [showTagInput, setShowTagInput] = useState(false);
  const [tagInput, setTagInput] = useState('');
  const [showMoveOptions, setShowMoveOptions] = useState(false);

  const folders = [
    { id: 'campaigns', name: 'Campanhas', icon: 'Folder' },
    { id: 'products', name: 'Produtos', icon: 'Package' },
    { id: 'social', name: 'Redes Sociais', icon: 'Share2' },
    { id: 'archive', name: 'Arquivo', icon: 'Archive' }
  ];

  const handleAddTags = () => {
    if (tagInput?.trim()) {
      const tags = tagInput?.split(',')?.map(tag => tag?.trim())?.filter(Boolean);
      onBulkTag(selectedItems, tags);
      setTagInput('');
      setShowTagInput(false);
    }
  };

  const handleMoveToFolder = (folderId) => {
    onBulkMove(selectedItems, folderId);
    setShowMoveOptions(false);
  };

  if (selectedItems?.length === 0) return null;

  return (
    <div className="fixed bottom-4 left-1/2 transform -translate-x-1/2 z-30">
      <div className="bg-card border border-border rounded-lg shadow-modal p-4 min-w-80">
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 bg-accent text-accent-foreground rounded-full flex items-center justify-center">
              <span className="text-sm font-body font-medium">
                {selectedItems?.length}
              </span>
            </div>
            <span className="font-body font-medium text-sm text-foreground">
              {selectedItems?.length === 1 ? 'item selecionado' : 'itens selecionados'}
            </span>
          </div>
          
          <Button
            variant="ghost"
            size="icon"
            onClick={onClearSelection}
            aria-label="Limpar seleção"
          >
            <Icon name="X" size={16} />
          </Button>
        </div>

        {/* Tag Input */}
        {showTagInput && (
          <div className="mb-4 p-3 bg-muted rounded-lg">
            <div className="flex gap-2">
              <Input
                type="text"
                placeholder="Digite as tags separadas por vírgula..."
                value={tagInput}
                onChange={(e) => setTagInput(e?.target?.value)}
                className="flex-1"
              />
              <Button
                variant="default"
                size="sm"
                onClick={handleAddTags}
                disabled={!tagInput?.trim()}
              >
                Adicionar
              </Button>
            </div>
          </div>
        )}

        {/* Move Options */}
        {showMoveOptions && (
          <div className="mb-4 p-3 bg-muted rounded-lg">
            <h4 className="font-body font-medium text-sm text-foreground mb-2">
              Mover para pasta:
            </h4>
            <div className="grid grid-cols-2 gap-2">
              {folders?.map((folder) => (
                <Button
                  key={folder?.id}
                  variant="outline"
                  size="sm"
                  onClick={() => handleMoveToFolder(folder?.id)}
                  className="flex items-center gap-2 justify-start"
                >
                  <Icon name={folder?.icon} size={14} />
                  {folder?.name}
                </Button>
              ))}
            </div>
          </div>
        )}

        {/* Action Buttons */}
        <div className="flex flex-wrap gap-2">
          <Button
            variant="outline"
            size="sm"
            onClick={() => {
              setShowTagInput(!showTagInput);
              setShowMoveOptions(false);
            }}
            iconName="Tag"
            iconPosition="left"
          >
            Tags
          </Button>

          <Button
            variant="outline"
            size="sm"
            onClick={() => {
              setShowMoveOptions(!showMoveOptions);
              setShowTagInput(false);
            }}
            iconName="FolderOpen"
            iconPosition="left"
          >
            Mover
          </Button>

          <Button
            variant="outline"
            size="sm"
            onClick={() => {
              // Handle bulk download
              console.log('Bulk download:', selectedItems);
            }}
            iconName="Download"
            iconPosition="left"
          >
            Baixar
          </Button>

          <Button
            variant="destructive"
            size="sm"
            onClick={() => onBulkDelete(selectedItems)}
            iconName="Trash2"
            iconPosition="left"
          >
            Excluir
          </Button>
        </div>
      </div>
    </div>
  );
};

export default BulkActions;