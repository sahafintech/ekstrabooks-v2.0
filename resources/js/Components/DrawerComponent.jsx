'use client';

import * as React from 'react';
import { cn } from '@/lib/utils';
import { X } from 'lucide-react';

const DrawerComponent = ({
  children,
  trigger,
  title,
  position = 'bottom',
  open,
  onOpenChange,
  className,
  overlayClassName,
  contentClassName,
  showCloseButton = true,
  closeOnOverlayClick = true,
  width = 'w-96', // default width, can be customized
  ...props
}) => {
  const [isOpen, setIsOpen] = React.useState(open || false);

  React.useEffect(() => {
    if (open !== undefined) {
      setIsOpen(open);
    }
  }, [open]);

  const handleOpenChange = (newOpen) => {
    setIsOpen(newOpen);
    onOpenChange?.(newOpen);
  };

  const handleOverlayClick = () => {
    if (closeOnOverlayClick) {
      handleOpenChange(false);
    }
  };

  const handleContentClick = (e) => {
    e.stopPropagation();
  };

  const getPositionClasses = () => {
    const baseClasses = 'fixed z-50 bg-background border shadow-lg';
    
    switch (position) {
      case 'top':
        return {
          content: `${baseClasses} inset-x-0 top-0 rounded-b-lg border-t-0 animate-in slide-in-from-top duration-300`,
          overlay: 'fixed inset-0 z-40 bg-black/50 animate-in fade-in duration-300'
        };
      case 'bottom':
        return {
          content: `${baseClasses} inset-x-0 bottom-0 rounded-t-lg border-b-0 animate-in slide-in-from-bottom duration-300`,
          overlay: 'fixed inset-0 z-40 bg-black/50 animate-in fade-in duration-300'
        };
      case 'left':
        return {
          content: `${baseClasses} inset-y-0 left-0 rounded-r-lg border-l-0 animate-in slide-in-from-left duration-300 ${width} max-w-[80vw]`,
          overlay: 'fixed inset-0 z-40 bg-black/50 animate-in fade-in duration-300'
        };
      case 'right':
        return {
          content: `${baseClasses} inset-y-0 right-0 rounded-l-lg border-r-0 animate-in slide-in-from-right duration-300 ${width} max-w-[80vw]`,
          overlay: 'fixed inset-0 z-40 bg-black/50 animate-in fade-in duration-300'
        };
      default:
        return {
          content: `${baseClasses} inset-x-0 bottom-0 rounded-t-lg border-b-0 animate-in slide-in-from-bottom duration-300`,
          overlay: 'fixed inset-0 z-40 bg-black/50 animate-in fade-in duration-300'
        };
    }
  };

  const { content: contentClasses, overlay: overlayClasses } = getPositionClasses();

  const getDragHandle = () => {
    if (position === 'bottom') {
      return <div className="mx-auto mt-4 h-2 w-[100px] rounded-full bg-muted" />;
    }
    if (position === 'top') {
      return <div className="mx-auto mb-4 h-2 w-[100px] rounded-full bg-muted" />;
    }
    if (position === 'left') {
      return <div className="my-auto mr-4 h-[100px] w-2 rounded-full bg-muted" />;
    }
    if (position === 'right') {
      return <div className="my-auto ml-4 h-[100px] w-2 rounded-full bg-muted" />;
    }
    return null;
  };

  if (!isOpen) {
    return (
      <div onClick={() => handleOpenChange(true)} className={className}>
        {trigger}
      </div>
    );
  }

  return (
    <>
      {/* Overlay */}
      <div
        className={cn(overlayClasses, overlayClassName)}
        onClick={handleOverlayClick}
      />
      
      {/* Drawer Content */}
      <div
        className={cn(contentClasses, contentClassName)}
        onClick={handleContentClick}
        {...props}
      >
        {/* Header with title and close button */}
        {title && (
          <div className={cn(
            'flex items-center justify-between p-4 border-b',
            (position === 'bottom' || position === 'top') && 'pt-8'
          )}>
            <h2 className="text-lg font-semibold leading-none tracking-tight">
              {title}
            </h2>
            {showCloseButton && (
              <button
                onClick={() => handleOpenChange(false)}
                className="p-2 rounded-full hover:bg-muted transition-colors"
              >
                <X className="h-4 w-4" />
                <span className="sr-only">Close</span>
              </button>
            )}
          </div>
        )}

        {/* Close Button (when no title) */}
        {!title && showCloseButton && (
          <button
            onClick={() => handleOpenChange(false)}
            className={cn(
              'absolute p-2 rounded-full hover:bg-muted transition-colors z-10',
              position === 'top' && 'top-4 right-4',
              position === 'bottom' && 'top-4 right-4',
              position === 'left' && 'top-4 right-4',
              position === 'right' && 'top-4 right-4'
            )}
          >
            <X className="h-4 w-4" />
            <span className="sr-only">Close</span>
          </button>
        )}

        {/* Drag Handle */}
        {(position === 'bottom' || position === 'top') && getDragHandle()}

        {/* Content */}
        <div className={cn(
          'flex flex-col',
          (position === 'left' || position === 'right') && 'h-[calc(100%-60px)]',
          position === 'bottom' && 'max-h-[80vh] pb-4 px-4',
          position === 'top' && 'max-h-[80vh] px-4 pb-4',
          title && 'pt-0',
          !title && (position === 'left' || position === 'right') && 'pt-12 h-full',
          !title && position === 'top' && 'pt-12',
          !title && position === 'bottom' && 'pt-4'
        )}>
          {children}
        </div>
      </div>
    </>
  );
};

// Header Component
const DrawerHeader = ({ className, children, ...props }) => (
  <div
    className={cn('grid gap-1.5 p-4 text-center sm:text-left', className)}
    {...props}
  >
    {children}
  </div>
);

// Title Component
const DrawerTitle = ({ className, children, ...props }) => (
  <h2
    className={cn('text-lg font-semibold leading-none tracking-tight', className)}
    {...props}
  >
    {children}
  </h2>
);

// Description Component
const DrawerDescription = ({ className, children, ...props }) => (
  <p
    className={cn('text-sm text-muted-foreground', className)}
    {...props}
  >
    {children}
  </p>
);

// Footer Component
const DrawerFooter = ({ className, children, ...props }) => (
  <div
    className={cn('mt-auto flex flex-col gap-2 p-4', className)}
    {...props}
  >
    {children}
  </div>
);

// Content wrapper for better organization
const DrawerBody = ({ className, children, ...props }) => (
  <div
    className={cn('p-4 flex-1', className)}
    {...props}
  >
    {children}
  </div>
);

export {
  DrawerComponent,
  DrawerHeader,
  DrawerTitle,
  DrawerDescription,
  DrawerFooter,
  DrawerBody
};

export default DrawerComponent;
