import {
    AlertDialog,
    AlertDialogContent,
  } from "@/components/ui/alert-dialog";
  
  export default function Modal({
    children,
    show = false,
    maxWidth = "2xl",
    closeable = true,
    onClose = () => {},
  }) {
    const maxWidthClass = {
      sm: "sm:max-w-sm",
      md: "sm:max-w-md",
      lg: "sm:max-w-lg",
      xl: "sm:max-w-xl",
      "2xl": "sm:max-w-2xl",
      "3xl": "sm:max-w-3xl",
      "4xl": "sm:max-w-4xl",
    }[maxWidth];
  
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
          className={`
            mb-6
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
  