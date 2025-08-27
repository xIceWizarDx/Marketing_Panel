import React, { createContext, useContext, useReducer, useCallback } from 'react';
import Icon from '../AppIcon';
import Button from './Button';

// Toast Context
const ToastContext = createContext();

// Toast Types
const TOAST_TYPES = {
  SUCCESS: 'success',
  ERROR: 'error',
  WARNING: 'warning',
  INFO: 'info'
};

// Toast Reducer
const toastReducer = (state, action) => {
  switch (action?.type) {
    case 'ADD_TOAST':
      return [...state, { ...action?.payload, id: Date.now() + Math.random() }];
    case 'REMOVE_TOAST':
      return state?.filter(toast => toast?.id !== action?.payload);
    case 'CLEAR_ALL':
      return [];
    default:
      return state;
  }
};

// Toast Provider Component
export const ToastProvider = ({ children }) => {
  const [toasts, dispatch] = useReducer(toastReducer, []);

  const addToast = useCallback((toast) => {
    dispatch({ type: 'ADD_TOAST', payload: toast });
    
    // Auto remove after duration
    if (toast?.duration !== 0) {
      setTimeout(() => {
        dispatch({ type: 'REMOVE_TOAST', payload: toast?.id || Date.now() });
      }, toast?.duration || 5000);
    }
  }, []);

  const removeToast = useCallback((id) => {
    dispatch({ type: 'REMOVE_TOAST', payload: id });
  }, []);

  const clearAll = useCallback(() => {
    dispatch({ type: 'CLEAR_ALL' });
  }, []);

  const value = {
    toasts,
    addToast,
    removeToast,
    clearAll,
    // Convenience methods
    success: (message, options = {}) => addToast({ ...options, type: TOAST_TYPES?.SUCCESS, message }),
    error: (message, options = {}) => addToast({ ...options, type: TOAST_TYPES?.ERROR, message }),
    warning: (message, options = {}) => addToast({ ...options, type: TOAST_TYPES?.WARNING, message }),
    info: (message, options = {}) => addToast({ ...options, type: TOAST_TYPES?.INFO, message })
  };

  return (
    <ToastContext.Provider value={value}>
      {children}
      <ToastContainer />
    </ToastContext.Provider>
  );
};

// Toast Hook
export const useToast = () => {
  const context = useContext(ToastContext);
  if (!context) {
    throw new Error('useToast must be used within a ToastProvider');
  }
  return context;
};

// Individual Toast Component
const Toast = ({ toast, onRemove }) => {
  const getToastStyles = (type) => {
    const baseStyles = "flex items-start gap-3 p-4 rounded-lg shadow-modal border transition-smooth";
    
    switch (type) {
      case TOAST_TYPES?.SUCCESS:
        return `${baseStyles} bg-success/10 border-success/20 text-success-foreground`;
      case TOAST_TYPES?.ERROR:
        return `${baseStyles} bg-error/10 border-error/20 text-error-foreground`;
      case TOAST_TYPES?.WARNING:
        return `${baseStyles} bg-warning/10 border-warning/20 text-warning-foreground`;
      case TOAST_TYPES?.INFO:
      default:
        return `${baseStyles} bg-accent/10 border-accent/20 text-accent-foreground`;
    }
  };

  const getIcon = (type) => {
    switch (type) {
      case TOAST_TYPES?.SUCCESS:
        return 'CheckCircle';
      case TOAST_TYPES?.ERROR:
        return 'XCircle';
      case TOAST_TYPES?.WARNING:
        return 'AlertTriangle';
      case TOAST_TYPES?.INFO:
      default:
        return 'Info';
    }
  };

  const getIconColor = (type) => {
    switch (type) {
      case TOAST_TYPES?.SUCCESS:
        return 'var(--color-success)';
      case TOAST_TYPES?.ERROR:
        return 'var(--color-error)';
      case TOAST_TYPES?.WARNING:
        return 'var(--color-warning)';
      case TOAST_TYPES?.INFO:
      default:
        return 'var(--color-accent)';
    }
  };

  return (
    <div className={getToastStyles(toast?.type)} role="alert">
      <Icon 
        name={getIcon(toast?.type)} 
        size={20} 
        color={getIconColor(toast?.type)}
        className="flex-shrink-0 mt-0.5"
      />
      <div className="flex-1 min-w-0">
        {toast?.title && (
          <div className="font-body font-medium text-sm mb-1">
            {toast?.title}
          </div>
        )}
        <div className="font-body text-sm text-foreground/90">
          {toast?.message}
        </div>
        {toast?.action && (
          <div className="mt-3">
            <Button
              variant="outline"
              size="sm"
              onClick={toast?.action?.onClick}
              className="text-xs"
            >
              {toast?.action?.label}
            </Button>
          </div>
        )}
      </div>
      <Button
        variant="ghost"
        size="icon"
        className="flex-shrink-0 w-6 h-6 -mt-1 -mr-1"
        onClick={() => onRemove(toast?.id)}
        aria-label="Fechar notificação"
      >
        <Icon name="X" size={14} />
      </Button>
    </div>
  );
};

// Toast Container Component
const ToastContainer = () => {
  const { toasts, removeToast } = useToast();

  if (toasts?.length === 0) return null;

  return (
    <div className="fixed top-20 right-4 z-60 space-y-3 max-w-sm w-full">
      {toasts?.map((toast) => (
        <div
          key={toast?.id}
          className="animate-in slide-in-from-right-full duration-300"
        >
          <Toast toast={toast} onRemove={removeToast} />
        </div>
      ))}
    </div>
  );
};

export default ToastProvider;