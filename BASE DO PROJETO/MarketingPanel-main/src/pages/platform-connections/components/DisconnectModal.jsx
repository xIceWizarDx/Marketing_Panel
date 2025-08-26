import React, { useState } from 'react';
import Icon from '../../../components/AppIcon';
import Button from '../../../components/ui/Button';
import { Checkbox } from '../../../components/ui/Checkbox';

const DisconnectModal = ({ platform, isOpen, onClose, onConfirm }) => {
  const [keepData, setKeepData] = useState(true);
  const [isDisconnecting, setIsDisconnecting] = useState(false);

  const handleDisconnect = async () => {
    setIsDisconnecting(true);
    try {
      await new Promise(resolve => setTimeout(resolve, 1500));
      onConfirm(platform?.id, { keepData });
      onClose();
    } catch (error) {
      console.error('Error disconnecting:', error);
    } finally {
      setIsDisconnecting(false);
    }
  };

  if (!isOpen || !platform) return null;

  return (
    <>
      {/* Backdrop */}
      <div 
        className="fixed inset-0 bg-black/50 z-50 transition-drawer"
        onClick={onClose}
      />
      {/* Modal */}
      <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div className="bg-card border border-border rounded-lg shadow-modal w-full max-w-md">
          {/* Header */}
          <div className="flex items-center gap-3 p-6 border-b border-border">
            <div className="w-10 h-10 bg-error/10 rounded-lg flex items-center justify-center">
              <Icon name="AlertTriangle" size={20} className="text-error" />
            </div>
            <div>
              <h2 className="font-body font-semibold text-lg text-card-foreground">
                Desconectar {platform?.name}
              </h2>
              <p className="text-sm text-muted-foreground">
                Esta ação não pode ser desfeita
              </p>
            </div>
          </div>

          {/* Content */}
          <div className="p-6 space-y-4">
            <div className="p-4 bg-warning/10 border border-warning/20 rounded-lg">
              <div className="flex items-start gap-3">
                <Icon name="AlertTriangle" size={16} className="text-warning mt-0.5" />
                <div className="text-sm text-warning-foreground">
                  <p className="font-medium mb-1">Atenção!</p>
                  <p>
                    Ao desconectar {platform?.name}, você perderá acesso às seguintes funcionalidades:
                  </p>
                  <ul className="mt-2 space-y-1 text-xs">
                    <li>• Publicação automática de conteúdo</li>
                    <li>• Sincronização de dados e métricas</li>
                    <li>• Agendamento de posts</li>
                    <li>• Análise de engajamento</li>
                  </ul>
                </div>
              </div>
            </div>

            <div className="space-y-3">
              <Checkbox
                label="Manter dados históricos"
                description="Preservar métricas e posts já sincronizados"
                checked={keepData}
                onChange={(e) => setKeepData(e?.target?.checked)}
              />
            </div>

            {platform?.accountInfo && (
              <div className="p-3 bg-muted/30 rounded-lg">
                <div className="text-sm">
                  <div className="font-medium text-card-foreground mb-1">
                    Conta conectada:
                  </div>
                  <div className="text-muted-foreground">
                    {platform?.accountInfo?.username} ({platform?.accountInfo?.email})
                  </div>
                </div>
              </div>
            )}
          </div>

          {/* Actions */}
          <div className="flex flex-col sm:flex-row gap-3 p-6 border-t border-border">
            <Button
              variant="outline"
              onClick={onClose}
              className="flex-1"
              disabled={isDisconnecting}
            >
              Cancelar
            </Button>
            <Button
              variant="destructive"
              onClick={handleDisconnect}
              loading={isDisconnecting}
              iconName="Unlink"
              iconPosition="left"
              className="flex-1"
            >
              {isDisconnecting ? 'Desconectando...' : 'Desconectar'}
            </Button>
          </div>
        </div>
      </div>
    </>
  );
};

export default DisconnectModal;