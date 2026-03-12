'use client';

import { useState } from 'react';

interface TimelineStep {
  id: string;
  title: string;
  description: string | null;
  dueDate: string;
  completed: boolean;
  order: number;
}

interface TimelineViewProps {
  steps: TimelineStep[];
}

export function TimelineView({ steps }: TimelineViewProps) {
  const [localSteps, setLocalSteps] = useState(steps);

  async function markComplete(id: string) {
    const res = await fetch(`/api/timeline/${id}/complete`, { method: 'POST' });
    if (res.ok) {
      setLocalSteps((prev) =>
        prev.map((s) => (s.id === id ? { ...s, completed: true } : s)),
      );
    }
  }

  const sorted = [...localSteps].sort((a, b) => a.order - b.order);

  return (
    <div className="relative">
      {/* Vertical line */}
      <div className="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200" />

      <div className="space-y-4 pl-12">
        {sorted.map((step, idx) => {
          const isOverdue = !step.completed && new Date(step.dueDate) < new Date();
          const dueDate = new Date(step.dueDate);
          const isUpcoming = !step.completed && !isOverdue;

          return (
            <div key={step.id} className="relative">
              {/* Dot */}
              <div
                className={`absolute -left-[2.2rem] top-3 w-4 h-4 rounded-full border-2 flex items-center justify-center ${
                  step.completed
                    ? 'bg-green-500 border-green-500'
                    : isOverdue
                    ? 'bg-red-100 border-red-400'
                    : 'bg-white border-brand-400'
                }`}
              >
                {step.completed && (
                  <svg className="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                  </svg>
                )}
              </div>

              <div
                className={`bg-white rounded-xl border p-4 ${
                  step.completed
                    ? 'border-gray-100 opacity-70'
                    : isOverdue
                    ? 'border-red-200 bg-red-50/30'
                    : idx === 0
                    ? 'border-brand-200 ring-1 ring-brand-100'
                    : 'border-gray-200'
                }`}
              >
                <div className="flex items-start justify-between gap-3">
                  <div className="flex-1">
                    <div className="flex items-center gap-2 flex-wrap">
                      <h4
                        className={`font-semibold text-sm ${
                          step.completed ? 'line-through text-gray-400' : 'text-gray-900'
                        }`}
                      >
                        {step.title}
                      </h4>
                      {isOverdue && (
                        <span className="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded-full font-medium">
                          Overdue
                        </span>
                      )}
                      {!step.completed && idx === 0 && !isOverdue && (
                        <span className="text-xs bg-brand-100 text-brand-700 px-2 py-0.5 rounded-full font-medium">
                          Next step
                        </span>
                      )}
                    </div>
                    {step.description && (
                      <p className="text-xs text-gray-500 mt-1">{step.description}</p>
                    )}
                    <p className={`text-xs mt-2 font-medium ${isOverdue ? 'text-red-600' : 'text-gray-500'}`}>
                      {isOverdue ? 'Was due' : 'Due'}: {dueDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                    </p>
                  </div>

                  {!step.completed && (
                    <button
                      onClick={() => markComplete(step.id)}
                      className="flex-shrink-0 text-xs bg-gray-100 hover:bg-green-100 hover:text-green-700 text-gray-600 px-3 py-1.5 rounded-lg font-medium transition"
                    >
                      Mark done
                    </button>
                  )}
                </div>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}
