import React from 'react';
import Button from '../../../components/ui/Button';

const QuickActionButton = ({ onClick }) => {
  return (
    <div className="fixed bottom-6 right-6 z-40">
      <Button
        size="lg"
        className="h-14 w-14 rounded-full shadow-modal hover:shadow-modal-hover"
        onClick={onClick}
        iconName="Plus"
        aria-label="Criar novo conteúdo"
      />
    </div>
  );
};

export default QuickActionButton;