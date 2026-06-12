import React from "react";

const ListDescription = ({ title, children }) => {
    return (
        <dl>
            <dt className="text-xs font-medium text-primary-500">{title}</dt>
            <dd className="mt-1 text-sm text-gray-600  block">{children}</dd>
        </dl>
    );
};

export default ListDescription;
