import {
    AlertDialog,
    AlertDialogContent,
  } from "@/Components/ui/alert-dialog";
  
  export default function Modal({
    children,
    show = false,
    maxWidth = "2xl",
    closeable = true,
    onClose = () => {},
  }) {
    const maxWidthClasses = {
      xs: "sm:max-w-xs",
      sm: "sm:max-w-sm",
      md: "sm:max-w-md",
      lg: "sm:max-w-lg",
      xl: "sm:max-w-xl",
      "2xl": "sm:max-w-2xl",
      "3xl": "sm:max-w-3xl",
      "4xl": "sm:max-w-4xl",
      "5xl": "sm:max-w-5xl",
      "6xl": "sm:max-w-6xl",
      "7xl": "sm:max-w-7xl",
      full: "sm:max-w-[calc(100vw-4rem)]",
    };

    const maxWidthClass = maxWidthClasses[maxWidth] ?? maxWidthClasses["2xl"];
  
    return (
      <AlertDialog
        open={show}
        onOpenChange={(open) => {
          if (!open && closeable) {
            onClose();
          }
        }}
      >
        <AlertDialogContent
          id="modal"
          size="modal"
          className={`
            mb-6
            w-[calc(100%-2rem)]
            max-h-[calc(100vh-4rem)]
            overflow-y-auto
            rounded-lg
            bg-white
            shadow-xl
            transition-all
            sm:mx-auto
            sm:w-full
            dark:bg-gray-800
            ${maxWidthClass}
          `}
        >
          {children}
        </AlertDialogContent>
      </AlertDialog>
    );
  }
  
