import { Editor } from '@tinymce/tinymce-react';

export default function RichTextEditor({ value, onChange, height = 250 }) {
    return (
        <Editor
            apiKey="ba9dk9kk4lidr4vbqipwnayl47dd1prfy9y6xqgc21svgcgu" // You'll need to get a free API key from https://www.tiny.cloud/
            value={value}
            onEditorChange={(content) => onChange(content)}
            init={{
                height: height,
                menubar: false,
                plugins: [
                    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                    'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                    'insertdatetime', 'media', 'table', 'code', 'help', 'wordcount'
                ],
                toolbar: 'undo redo | blocks | ' +
                    'bold italic forecolor | alignleft aligncenter ' +
                    'alignright alignjustify | bullist numlist outdent indent | ' +
                    'removeformat | help',
                content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
            }}
        />
    );
} 