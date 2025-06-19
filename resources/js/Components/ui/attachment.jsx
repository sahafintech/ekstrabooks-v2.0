import React from "react";
import { X } from "lucide-react";

export default function Attachment({
    files = [],
    onAdd = () => {},
    onRemove = () => {},
    maxSize = 20,
    databaseAttachments = [],
}) {
    return (
        <div className="w-full">
            <div className="font-semibold mb-2">Attachments</div>
            <div className="border rounded-md bg-white">
                {files.length === 0 && files.length === 0 && (
                    <div className="text-gray-400 text-sm p-4 text-center">
                        No attachments
                    </div>
                )}
                {files.map((file, idx) => (
                    <div
                        key={`file-${idx}`}
                        className="flex items-center px-4 py-2 border-b last:border-b-0 hover:bg-gray-50 group"
                    >
                        <span className="truncate flex-1 ml-2">
                            <span className="text-blue-700 hover:underline cursor-pointer text-sm font-medium">
                                {databaseAttachments.find(
                                    (attachment) => attachment.path === file
                                )?.file_name || file.name}
                            </span>
                            <span className="ml-2 text-xs text-gray-500">
                                {file.size >= 1024 * 1024
                                    ? `${(file.size / (1024 * 1024)).toFixed(
                                          1
                                      )}MB`
                                    : `${(file.size / 1024).toFixed(1)}KB`}
                            </span>
                        </span>
                        <button
                            type="button"
                            onClick={() => onRemove(idx)}
                            className="ml-2 text-gray-400 hover:text-red-600"
                            title="Remove file"
                        >
                            <X className="w-5 h-5" />
                        </button>
                    </div>
                ))}
            </div>
            <div className="flex flex-col items-start mt-2">
                <label className="inline-block cursor-pointer">
                    <span className="inline-block px-4 py-2 border border-dashed border-gray-400 rounded-md bg-gray-50 hover:bg-gray-100 text-blue-700 font-medium text-sm">
                        Add attachment
                    </span>
                    <input
                        type="file"
                        multiple
                        className="hidden"
                        onChange={(e) => {
                            const files = Array.from(e.target.files);
                            onAdd(files);
                            e.target.value = null;
                        }}
                    />
                </label>
                <span className="text-xs text-gray-500 mt-1">
                    Max file size: {maxSize} MB
                </span>
            </div>
        </div>
    );
}
