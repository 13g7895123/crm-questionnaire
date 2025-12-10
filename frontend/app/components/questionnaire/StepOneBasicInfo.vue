<template>
  <div class="space-y-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">公司基本資料</h2>

    <!-- 区块一：公司信息 -->
    <div class="border-b pb-6">
      <h3 class="text-xl font-semibold text-gray-700 mb-4">公司資訊</h3>
      <div class="grid grid-cols-1 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            公司全名 <span class="text-red-500">*</span>
          </label>
          <input
            v-model="formData.companyFullName"
            type="text"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="請輸入公司全名"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            公司地址 <span class="text-red-500">*</span>
          </label>
          <textarea
            v-model="formData.companyAddress"
            rows="3"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="請輸入完整地址"
          ></textarea>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            公司總營收 (USD) <span class="text-red-500">*</span>
          </label>
          <input
            v-model.number="formData.totalRevenue"
            type="number"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="請輸入金額"
          />
        </div>
      </div>
    </div>

    <!-- 区块二：厂区信息 -->
    <div class="border-b pb-6">
      <h3 class="text-xl font-semibold text-gray-700 mb-4">廠區資訊</h3>
      
      <div
        v-for="(facility, index) in formData.facilities"
        :key="index"
        class="bg-gray-50 p-4 rounded-lg mb-4"
      >
        <div class="flex justify-between items-center mb-4">
          <h4 class="font-medium text-gray-700">廠區 {{ index + 1 }}</h4>
          <button
            v-if="formData.facilities.length > 1"
            @click="removeFacility(index)"
            class="text-red-500 hover:text-red-700 text-sm"
          >
            刪除
          </button>
        </div>

        <div class="grid grid-cols-1 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              製造廠區全名 <span class="text-red-500">*</span>
            </label>
            <input
              v-model="facility.name"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="請輸入廠區全名"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              製造廠區地址 <span class="text-red-500">*</span>
            </label>
            <textarea
              v-model="facility.address"
              rows="2"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="請輸入完整地址"
            ></textarea>
          </div>

          <!-- 厂区员工人数 -->
          <div class="bg-white p-4 rounded border">
            <h5 class="font-medium text-gray-700 mb-3">廠區員工人數（全職員工）</h5>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm text-gray-600 mb-1">本國籍員工 - 男</label>
                <input
                  v-model.number="facility.employees.localMale"
                  type="number"
                  min="0"
                  class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
              <div>
                <label class="block text-sm text-gray-600 mb-1">本國籍員工 - 女</label>
                <input
                  v-model.number="facility.employees.localFemale"
                  type="number"
                  min="0"
                  class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
              <div>
                <label class="block text-sm text-gray-600 mb-1">外國籍員工 - 男</label>
                <input
                  v-model.number="facility.employees.foreignMale"
                  type="number"
                  min="0"
                  class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
              <div>
                <label class="block text-sm text-gray-600 mb-1">外國籍員工 - 女</label>
                <input
                  v-model.number="facility.employees.foreignFemale"
                  type="number"
                  min="0"
                  class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
            </div>
            <div class="mt-2 text-sm text-gray-600">
              總計：{{ calculateTotalEmployees(facility) }} 人
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              提供的服務/產品項目
            </label>
            <textarea
              v-model="facility.servicesProducts"
              rows="2"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="請描述主要服務或產品"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              管理系統認證
            </label>
            <div class="space-y-2">
              <label class="flex items-center">
                <input
                  v-model="facility.certifications"
                  type="checkbox"
                  value="ISO9001"
                  class="mr-2"
                />
                ISO 9001 (品質管理)
              </label>
              <label class="flex items-center">
                <input
                  v-model="facility.certifications"
                  type="checkbox"
                  value="ISO14001"
                  class="mr-2"
                />
                ISO 14001 (環境管理)
              </label>
              <label class="flex items-center">
                <input
                  v-model="facility.certifications"
                  type="checkbox"
                  value="ISO45001"
                  class="mr-2"
                />
                ISO 45001 (職業安全衛生)
              </label>
              <label class="flex items-center">
                <input
                  v-model="facility.certifications"
                  type="checkbox"
                  value="IATF16949"
                  class="mr-2"
                />
                IATF 16949 (汽車產業)
              </label>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              RBA-Online System
            </label>
            <div class="space-y-2">
              <label class="flex items-center">
                <input
                  v-model="facility.rbaOnline"
                  type="radio"
                  value="yes"
                  class="mr-2"
                />
                已註冊
              </label>
              <label class="flex items-center">
                <input
                  v-model="facility.rbaOnline"
                  type="radio"
                  value="no"
                  class="mr-2"
                />
                未註冊
              </label>
              <label class="flex items-center">
                <input
                  v-model="facility.rbaOnline"
                  type="radio"
                  value="planning"
                  class="mr-2"
                />
                規劃中
              </label>
            </div>
          </div>
        </div>
      </div>

      <button
        @click="addFacility"
        class="mt-4 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
      >
        + 新增廠區
      </button>
    </div>

    <!-- 区块三：联络信息 -->
    <div class="pb-6">
      <h3 class="text-xl font-semibold text-gray-700 mb-4">聯絡信息</h3>
      
      <div
        v-for="(contact, index) in formData.contacts"
        :key="index"
        class="bg-gray-50 p-4 rounded-lg mb-4"
      >
        <div class="flex justify-between items-center mb-4">
          <h4 class="font-medium text-gray-700">聯絡人 {{ index + 1 }}</h4>
          <button
            v-if="formData.contacts.length > 1"
            @click="removeContact(index)"
            class="text-red-500 hover:text-red-700 text-sm"
          >
            刪除
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              聯絡人員 <span class="text-red-500">*</span>
            </label>
            <input
              v-model="contact.name"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="請輸入姓名"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              職稱 <span class="text-red-500">*</span>
            </label>
            <input
              v-model="contact.title"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="請輸入職稱"
            />
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Email <span class="text-red-500">*</span>
            </label>
            <input
              v-model="contact.email"
              type="email"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="example@company.com"
            />
          </div>
        </div>
      </div>

      <button
        @click="addContact"
        class="mt-4 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
      >
        + 新增聯絡人
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
interface FacilityData {
  name: string;
  address: string;
  employees: {
    localMale: number;
    localFemale: number;
    foreignMale: number;
    foreignFemale: number;
  };
  servicesProducts: string;
  certifications: string[];
  rbaOnline: string;
}

interface ContactData {
  name: string;
  title: string;
  email: string;
}

interface FormData {
  companyFullName: string;
  companyAddress: string;
  totalRevenue: number | null;
  facilities: FacilityData[];
  contacts: ContactData[];
}

const formData = defineModel<FormData>({
  default: {
    companyFullName: '',
    companyAddress: '',
    totalRevenue: null,
    facilities: [
      {
        name: '',
        address: '',
        employees: {
          localMale: 0,
          localFemale: 0,
          foreignMale: 0,
          foreignFemale: 0,
        },
        servicesProducts: '',
        certifications: [],
        rbaOnline: '',
      },
    ],
    contacts: [
      {
        name: '',
        title: '',
        email: '',
      },
    ],
  },
});

const addFacility = () => {
  formData.value.facilities.push({
    name: '',
    address: '',
    employees: {
      localMale: 0,
      localFemale: 0,
      foreignMale: 0,
      foreignFemale: 0,
    },
    servicesProducts: '',
    certifications: [],
    rbaOnline: '',
  });
};

const removeFacility = (index: number) => {
  formData.value.facilities.splice(index, 1);
};

const addContact = () => {
  formData.value.contacts.push({
    name: '',
    title: '',
    email: '',
  });
};

const removeContact = (index: number) => {
  formData.value.contacts.splice(index, 1);
};

const calculateTotalEmployees = (facility: FacilityData) => {
  return (
    facility.employees.localMale +
    facility.employees.localFemale +
    facility.employees.foreignMale +
    facility.employees.foreignFemale
  );
};
</script>
