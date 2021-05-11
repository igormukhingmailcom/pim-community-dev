import React from 'react';
import {renderHook} from '@testing-library/react-hooks';
import {TableContext} from '../TableContext';
import {ReactNode} from 'react';
import {useDrop} from './useDrop';

const onReorder = jest.fn();
const wrapper = ({children}: {children: ReactNode}) => (
  <TableContext.Provider
    value={{isSelectable: false, displayCheckbox: false, isDragAndDroppable: true, onReorder: onReorder}}
  >
    {children}
  </TableContext.Provider>
);

test('it does not call on reorder callback when user try to drag and drop to another table', () => {
  const {result} = renderHook(() => useDrop(10, 1), {wrapper});

  const [, handleDrop] = result.current;
  handleDrop({
    currentTarget: {
      dataset: {
        tableId: 'another_table_id',
      },
    },
    target: {
      dataset: {},
      parentElement: {
        dataset: {
          draggableIndex: '2',
        } as any,
      } as any,
    },
  } as any);

  expect(onReorder).not.toHaveBeenCalled();
});

test('it does not on reorder callback when user drag and drop', () => {
  const stopPropagation = jest.fn();
  const {result} = renderHook(() => useDrop(4, 1), {wrapper});

  const [tableId, handleDrop] = result.current;
  handleDrop({
    currentTarget: {
      dataset: {
        tableId,
      },
    },
    target: {
      dataset: {},
      parentElement: {
        dataset: {
          draggableIndex: '2',
        } as any,
      } as any,
    },
    stopPropagation,
  } as any);

  expect(onReorder).toHaveBeenCalledWith([0, 2, 1, 3]);
  expect(stopPropagation).toHaveBeenCalled();
});

test('it throw an error when we cannot find the target index', () => {
  const {result} = renderHook(() => useDrop(4, 1), {wrapper});

  const [tableId, handleDrop] = result.current;

  expect(() =>
    handleDrop({
      currentTarget: {
        dataset: {
          tableId,
        },
      },
      target: {
        dataset: {},
        parentElement: null,
      },
      stopPropagation: jest.fn,
    } as any)
  ).toThrowError('Draggable parent not found');
});

test('it does not call on reorder callback on dragover', () => {
  const stopPropagation = jest.fn();
  const preventDefault = jest.fn();
  const {result} = renderHook(() => useDrop(4, 1), {wrapper});

  const [, , handleDragOver] = result.current;
  handleDragOver({
    stopPropagation,
    preventDefault,
  } as any);

  expect(stopPropagation).toHaveBeenCalled();
  expect(preventDefault).toHaveBeenCalled();
  expect(onReorder).not.toBeCalled();
});