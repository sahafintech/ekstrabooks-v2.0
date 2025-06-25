import { parseDateObject } from "@/lib/utils";
import React, { useRef, useEffect } from "react";
import { Gantt } from "wx-react-gantt";
import "wx-react-gantt/dist/gantt.css";

export default function GanttChartComponent({ project }) {
  const tasks = [
    ...project.tasks.map((task) => ({
      id: task.id,
      text: task.description,
      start: task.start_date ? parseDateObject(task.start_date) : new Date(),
      end: task.end_date ? parseDateObject(task.end_date) : new Date(),
      duration: task.end_date && task.start_date ? (parseDateObject(task.end_date) - parseDateObject(task.start_date)) / (1000 * 60 * 60 * 24) + 1 : 0,
      progress: task.completed_percent || 0,
      type: "task",
      lazy: false,
    })),
  ];

  const links = [{ id: 1, source: 20, target: 21, type: "e2e" }];

  const scales = [
    { unit: "month", step: 1, format: "MMMM yyy" },
    { unit: "day", step: 1, format: "d" },
  ];

  return <Gantt tasks={tasks} links={links} scales={scales} />;
};