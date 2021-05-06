import React, {useEffect, useState} from 'react';
import ReactDOM from 'react-dom';
import {DependenciesProvider} from '@akeneo-pim-community/legacy-bridge';
import {
  Channel,
  Category,
  CategoryTree,
  CategoryTreeModel,
  CategoryValue,
  useIsMounted, useRoute
} from '@akeneo-pim-community/shared';
import styled, {ThemeProvider} from 'styled-components';
import {pimTheme, TabBar} from 'akeneo-design-system';
import {Tree} from 'akeneo-design-system/lib';
import {CategoryResponse, parseResponse} from '../../../CategoryTreeFetcher';
const UserContext = require('pim/user-context');
const __ = require('oro/translator');
const Router = require('pim/router');
const FetcherRegistry = require('pim/fetcher-registry');
import BaseView = require('pimui/js/view/base');

const CategoryTreeContainer = styled.div`
  max-height: calc(100vh - 240px);
  overflow: hidden auto;
`;

type CategorySelectorWithAllProductsProps = {
  categoryTreeCode: string,
  onChange: (value: string[]) => void;
  initialCategoryCodes: string[];
};

const CategorySelectorWithAllProducts: React.FC<CategorySelectorWithAllProductsProps> = ({
  categoryTreeCode,
  onChange,
  initialCategoryCodes,
}) => {
  const [categoryCodes, setCategoryCodes] = React.useState<string[]>(initialCategoryCodes);

  const isCategorySelected: (category: CategoryValue) => boolean = category => {
    return categoryCodes.includes(category.code);
  };

  const handleChange = (value: string, checked: boolean) => {
    const index = categoryCodes.indexOf(value, 0);
    if (checked) {
      if (index <= -1) {
        categoryCodes.push(value);
        setCategoryCodes([...categoryCodes]);
        onChange(categoryCodes);
      }
    } else {
      if (index > -1) {
        categoryCodes.splice(index, 1);
        setCategoryCodes([...categoryCodes]);
        onChange(categoryCodes);
      }
    }
  };

  const getChildrenUrl = (id: number) => {
    return Router.generate('pim_enrich_categorytree_children', {
      _format: 'json',
      id,
    });
  };

  const childrenCallback: (id: number) => Promise<CategoryTreeModel[]> = async id => {
    const response = await fetch(getChildrenUrl(id));
    const json: CategoryResponse[] = await response.json();

    return json.map(child =>
      parseResponse(child, {
        selectable: true,
      })
    );
  };

  const init = async () => {
    await FetcherRegistry.initialize();
    const category: Category = await FetcherRegistry.getFetcher('category').fetch(categoryTreeCode);
    const response = await fetch(getChildrenUrl(category.id));
    const json: CategoryResponse[] = await response.json();

    return {
      id: category.id,
      code: category.code,
      label: category.labels[UserContext.get('catalogLocale')] || `[${category.code}]`,
      selectable: false,
      children: json.map(child =>
        parseResponse(child, {
          selectable: true,
        })
      ),
    };
  };

  return (
    <CategoryTreeContainer>
      <CategoryTree
        onChange={handleChange}
        childrenCallback={childrenCallback}
        init={init}
        isCategorySelected={isCategorySelected}
      />
      <Tree
        value={'all'}
        label={__('jstree.all')}
        isLeaf={true}
        selectable={true}
        selected={categoryCodes.length === 0}
        onChange={(_value, checked) => {
          if (checked) {
            setCategoryCodes([]);
            onChange([]);
          }
        }}
      />
    </CategoryTreeContainer>
  );
};

type SelectorConfig = {
  el: any;
  attributes: {
    channel: string;
    categories: string[];
  };
};

const MultiCategoryTreeSelector = ({categoriesSelected, onCategorySelected}: {categoriesSelected: string[], onCategorySelected: (categoriesSelected: string[]) => void}) => {
  const [activeCategoryTree, setActiveCategoryTree] = useState<string>('');
  const [categoryTrees, setCategoryTrees] = useState<{code: string, labels: {fr_FR: string}}[]>([]);
  const isMounted = useIsMounted();
  const route = useRoute('pim_enrich_category_rest_list');

  useEffect(() => {
    (async () => {
      const response = await fetch(route);
      const json = await response.json();
      if (isMounted()) {
        setCategoryTrees(json);
        setActiveCategoryTree(json[0].code);
      }
    })()
  }, [route, setCategoryTrees, isMounted]);

  return (
    <>
      <TabBar>
        {categoryTrees.map((categoryTree) => {
          return (
            <TabBar.Tab isActive={activeCategoryTree === categoryTree.code} onClick={() => {
              setActiveCategoryTree('');
              setTimeout(() => {
                setActiveCategoryTree(categoryTree.code);
              }, 0)
            }}>
              {categoryTree.labels.fr_FR}
            </TabBar.Tab>
          )
        })}
      </TabBar>
      <div>
        {activeCategoryTree && (
          <CategorySelectorWithAllProducts
            categoryTreeCode={activeCategoryTree}
            initialCategoryCodes={categoriesSelected}
            onChange={onCategorySelected}
          />
        )}
      </div>
    </>
  );
}
class Selector extends BaseView {
  private categoryCodes: string[];

  constructor(options: SelectorConfig) {
    super(options);
    this.categoryCodes = options.attributes.categories || [];
  }

  render() {
    const handleChange = (categoryCodes: string[]) => {
      this.categoryCodes = categoryCodes;
    };

    ReactDOM.render(
      <DependenciesProvider>
        <ThemeProvider theme={pimTheme}>
          <MultiCategoryTreeSelector categoriesSelected={this.categoryCodes} onCategorySelected={handleChange} />
        </ThemeProvider>
      </DependenciesProvider>,
      this.$el[0]
    );

    return this;
  }

  public getCategoryCodes: () => string[] = () => {
    return this.categoryCodes;
  };
}

export = Selector;
