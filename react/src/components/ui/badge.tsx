import * as React from "react";
import { cva, type VariantProps } from "class-variance-authority";

import { cn } from "@/lib/utils";

const badgeVariants = cva(
  "inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2",
  {
    variants: {
      variant: {
        default:
          "border-transparent bg-primary text-primary-foreground hover:bg-primary/80",
        secondary:
          "border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80",
        successStatus: "border-transparent bg-green-500/20 text-green-500",
        warning: "border-transparent bg-yellow-500/20 text-yellow-500",
        orange: "border-transparent bg-orange-500/20 text-orange-500",
        dimmedStatus: "border-transparent bg-gray-500/20 text-gray-500",
        progressStatus: "border-transparent bg-orange-500/20 text-orange-500",
        destructiveStatus:
          "border-transparent bg-destructive/20 text-destructive",
        successOutline:
          "text-foreground text-green-600 border-green-600 border-green-600 shadow hover:border-green-600/80",
        destructiveOutline:
          "text-destructive border-destructive shadow hover:border-destructive/80",
        destructive:
          "border-transparent bg-destructive text-destructive-foreground hover:bg-destructive/80",
        outline: "text-foreground",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  }
);

export interface BadgeProps
  extends React.HTMLAttributes<HTMLDivElement>,
    VariantProps<typeof badgeVariants> {}

function Badge({ className, variant, ...props }: BadgeProps) {
  return (
    <div className={cn(badgeVariants({ variant }), className)} {...props} />
  );
}

export { Badge, badgeVariants };
