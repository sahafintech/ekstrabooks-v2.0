"use client";

import React from "react";

// shadcn/ui components (adjust import paths as needed)
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Dialog,
  DialogTrigger,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  Tabs,
  TabsList,
  TabsTrigger,
  TabsContent,
} from "@/components/ui/tabs";
import { ScrollArea } from "@/components/ui/scroll-area";
import { Link } from "@inertiajs/react";

export default function POS() {
  return (
    // Full screen height layout in a flex column
    <div className="h-screen flex flex-col">
      {/* -------------------------
          TOP BAR: Search + Dialog Buttons
      ------------------------- */}
      <div className="p-4 space-y-4">
        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
          <Input
            placeholder="search product / name / item code / scan bar code"
            className="flex-1"
          />

          <div className="flex flex-wrap items-center gap-2">
            {/* Hold List */}
            <Dialog>
              <DialogTrigger asChild>
                <Button variant="default">Hold List</Button>
              </DialogTrigger>
              <DialogContent>
                <DialogHeader>
                  <DialogTitle>Hold List</DialogTitle>
                </DialogHeader>
              </DialogContent>
            </Dialog>

            {/* Today Invoices */}
            <Dialog>
              <DialogTrigger asChild>
                <Button variant="default">Today Invoices</Button>
              </DialogTrigger>
              <DialogContent>
                <DialogHeader>
                  <DialogTitle>Today Invoices</DialogTitle>
                </DialogHeader>
              </DialogContent>
            </Dialog>

            {/* Appointment */}
            <Dialog>
              <DialogTrigger asChild>
                <Button variant="default">Appointment</Button>
              </DialogTrigger>
              <DialogContent>
                <DialogHeader>
                  <DialogTitle>Appointment</DialogTitle>
                </DialogHeader>
              </DialogContent>
            </Dialog>

            {/* Prescriptions */}
            <Dialog>
              <DialogTrigger asChild>
                <Button variant="default">Prescriptions</Button>
              </DialogTrigger>
              <DialogContent>
                <DialogHeader>
                  <DialogTitle>Prescriptions</DialogTitle>
                </DialogHeader>
              </DialogContent>
            </Dialog>

            <Link href={route('dashboard.index')}>
              <Button variant="secondary">Back</Button>
            </Link>
          </div>
        </div>
      </div>

      {/* ------------------------------------------------
          MAIN CONTENT AREA (fills remaining height)
          Responsive 2-column grid: stacked on small screens,
          50/50 columns on medium and larger screens.
      ------------------------------------------------ */}
      <div className="flex-1 overflow-hidden px-4 pb-4">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 h-full">
          {/* -------------------------------
              LEFT COLUMN: CART / SELECTED ITEMS
          ------------------------------- */}
          <div className="border rounded-md p-4 flex flex-col h-full">
            {/* Table header (visible on md and above) */}
            <div className="hidden md:grid grid-cols-[2fr_1fr_1fr_1fr_auto] font-semibold border-b pb-2">
              <span>PRODUCT</span>
              <span>PRICE</span>
              <span>QTY</span>
              <span>SUBTOTAL</span>
              <span>ACTION</span>
            </div>

            {/* Scrollable container for items & totals */}
            <div className="flex-1 overflow-y-auto space-y-4">
              {/* Example row */}
              <div className="grid grid-cols-[2fr_1fr_1fr_1fr_auto] items-center border-b py-2 gap-2">
                <span>Chi-Duck Child Plastic Full Frame 2448</span>
                <span>$35.00</span>
                <span>1</span>
                <span>$35.00</span>
                <Button variant="destructive">Remove</Button>
              </div>

              {/* Totals section */}
              <div className="text-right space-y-1">
                <div>Sub Total: $35</div>
                <div>Tax: $0</div>
                <div>Grand Total: $35</div>
              </div>
            </div>

            {/* Action buttons fixed at the bottom */}
            <div className="mt-4">
              <div className="flex flex-wrap justify-end gap-2">
                <Button variant="default">Pay</Button>
                <Button variant="default">Tax</Button>
                <Button variant="default">Cancel</Button>
                <Button variant="default">Discount</Button>
                <Button variant="default">Hold</Button>
              </div>
            </div>
          </div>

          {/* -------------------------------
              RIGHT COLUMN: PRODUCT LISTING
          ------------------------------- */}
          <div className="border rounded-md p-4 flex flex-col h-full">
            <Tabs defaultValue="all" className="flex flex-col h-full">
              {/* Horizontal tabs, scrollable */}
              <TabsList className="flex gap-2 overflow-x-auto whitespace-nowrap">
                <TabsTrigger value="all">All</TabsTrigger>
                <TabsTrigger value="adult">Adult Prescription Glasses</TabsTrigger>
                <TabsTrigger value="spherical">Spherical Lenses</TabsTrigger>
                <TabsTrigger value="single-vision">Single Vision Lenses</TabsTrigger>
                <TabsTrigger value="compound">Compound Spherical Cylinder Lenses</TabsTrigger>
              </TabsList>

              {/* Scrollable product content */}
              <div className="flex-1 overflow-y-auto mt-4">
                {/* All Tab */}
                <TabsContent value="all">
                  <ScrollArea className="max-h-[400px]">
                    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-4 gap-4">
                      <Button variant="outline" className="h-20">
                        Checkup Service
                      </Button>
                      <Button variant="outline" className="h-20">
                        Ch-Duck Child Plastic Full Frame 2540
                      </Button>
                      <Button variant="outline" className="h-20">
                        Ch-Duck Child Plastic Full Frame 2543
                      </Button>
                      <Button variant="outline" className="h-20">
                        Ch-Duck Child Plastic Full Frame 6009
                      </Button>
                      <Button variant="outline" className="h-20">
                        Ch-Duck Child Plastic Full Frame 2530
                      </Button>
                      <Button variant="outline" className="h-20">
                        Ch-Duck Child Plastic Full Frame 2448
                      </Button>
                    </div>
                  </ScrollArea>
                </TabsContent>

                {/* Adult Tab */}
                <TabsContent value="adult">
                  <ScrollArea className="max-h-[400px]">
                    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-4 gap-4">
                      <Button variant="outline" className="h-20">
                        Adult Glass #1
                      </Button>
                      <Button variant="outline" className="h-20">
                        Adult Glass #2
                      </Button>
                    </div>
                  </ScrollArea>
                </TabsContent>

                {/* Spherical Lenses Tab */}
                <TabsContent value="spherical">
                  <ScrollArea className="max-h-[400px]">
                    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-4 gap-4">
                      <Button variant="outline" className="h-20">
                        Spherical Lens #1
                      </Button>
                      <Button variant="outline" className="h-20">
                        Spherical Lens #2
                      </Button>
                    </div>
                  </ScrollArea>
                </TabsContent>

                {/* Single Vision Lenses Tab */}
                <TabsContent value="single-vision">
                  <ScrollArea className="max-h-[400px]">
                    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-4 gap-4">
                      <Button variant="outline" className="h-20">
                        Single Vision #1
                      </Button>
                    </div>
                  </ScrollArea>
                </TabsContent>

                {/* Compound Spherical Cylinder Tab */}
                <TabsContent value="compound">
                  <ScrollArea className="max-h-[400px]">
                    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-4 gap-4">
                      <Button variant="outline" className="h-20">
                        Compound Lens #1
                      </Button>
                    </div>
                  </ScrollArea>
                </TabsContent>
              </div>
            </Tabs>
          </div>
        </div>
      </div>
    </div>
  );
}
